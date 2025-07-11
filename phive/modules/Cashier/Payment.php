<?php

require_once __DIR__ . '/CashierCommon.php';

/**
 * Class Payment
 *
 * For now this class is only use from laraphive/syx-payments-api to provide ApplePay on the mobile app trough the API
 *
 * @api
 */
abstract class Payment extends CashierCommon
{
    /**
     * @var string
     */
    private string $error = '';

    /**
     * @var bool
     */
    private bool $isApi;

    /**
     * @param string $action
     * @param bool $isApi
     * @param string $context
     * @param $u_obj
     */
    public function __construct(string $action, bool $isApi = false, string $context = 'phive', $u_obj = null)
    {
        $this->isApi = $isApi;
        $this->action = $action;
        $res = parent::init($context, $u_obj);

        if (is_string($res)) {
            $this->setError($res);
        }
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function setReloadCode(array $args): void
    {
        $reload = phive('Bonuses')->getReload($args['bonus_code'], '', true, $this->u_obj);
        if(!empty($reload)) {
            phive('Bonuses')->setCurReload(trim($args['bonus_code']));
        } else {
            $this->setError('bonus.code.invalid');
        }
    }

    /**
     * @param array $args
     *
     * @return array|false
     */
    public function execute(array $args)
    {
        $isSupplierActive = $this->checkIfSupplierActive();

        if (!$isSupplierActive) {
            $this->setError('psp.supplier.not.active');
            return false;
        }

        if ($args['action'] == 'validatemerchant') {
            return $this->validateMerchantCall($args['extUrl']);
        }

        $repeat_id = phive()->rmNonNums($args['repeat_id']);

        if ($this->action == 'repeat' && empty($repeat_id)) {
            $this->setError('err.repeat.no.id');
            return false;
        }

        $this->handleNicExtra($args);

        list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $this->limits_supplier, 'in');

        if (!empty($err)) {
            $this->setError(array_shift($err));
            return false;
        }

        $cents = $amount * 100;

        list($res, $action) = $this->cashier->checkOverLimits($this->u_obj, $cents);
        if ($args['deposit_type'] != 'undo' && $res) {
            $this->error_action = $action;
            $this->setError('deposits.over.limit.html');
            return false;
        }

        // Jurisdiction specific validations before a deposit.
        $err_msg = lic('validateDeposit', [$this->u_obj, 'bank', $args], $this->u_obj);
        if ($err_msg) {
            $this->setError($err_msg);
            return false;
        }

        $extra = $this->setExtraData($args);
        if ($this->hasError()) {
            return false;
        }

        $failover = [];

        if ($this->action == 'deposit') {
            $res = $this->mts->deposit($this->u_obj, $cents, array_merge($this->extra, $extra));
            if (!$res['success']) {
                $failover['failover'] = $this->cashier->addAndGetFailover($this->supplier, $this->limits_supplier);
            }
        } else {
            $res = $this->mts->depositRepeat($repeat_id, 0, 0, 0, 'recurring', $extra);
        }

        if (!$res['success']) {
            $res = array_merge($res, $failover);
        } else {
            rgLimits()->addPendingDeposit($this->u_obj, $cents);
        }

        return $res;
    }

    /**
     * @param string $extUrl
     *
     * @return array|void
     */
    private function validateMerchantCall(string $extUrl)
    {
        $params = [];

        if ($this->sub_supplier == 'applepay') {
            $params['apple_endpoint'] = $extUrl;
            $params['display_name'] = phive()->getSetting('domain');
            $params['initiative_context'] = $_SERVER['HTTP_HOST'];
        }

        $res = $this->mts->validatemerchant($params);

        if (!$res['success']) {
            $res = $this->cashier->transferEnd(implode('. ', $res['errors']), false);
            $res = $this->fallbackDepositDecorator->decorate($res);
            die( json_encode( $res ) );
        }

        return $this->stop($res);
    }

    /**
     * @param array $args
     *
     * @return void
     */
    private function handleNicExtra(array $args): void
    {
        if (!empty($args['nid_extra']) && !$this->u_obj->hasSetting('nid_extra')) {
            $this->u_obj->setSetting('nid_extra', phive()->rmWhiteSpace($args['nid_extra']));
        }
    }

    /**
     * @return bool
     */
    private function checkIfSupplierActive(): bool
    {
        return $this->cashier->withdrawDepositAllowed($this->u_obj, $this->limits_supplier, 'deposit');
    }

    /**
     * @param string $error
     *
     * @return void
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->error);
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param array $arr
     *
     * @return array|void
     */
    public function stop($arr)
    {
        if ($this->isApi) {
            return $arr;
        }

        parent::stop($arr);
    }

    /**
     * @param array $args
     *
     * @return array|false
     */
    abstract public function deposit(array $args);

    /**
     * @param array $args
     *
     * @return array
     */
    abstract protected function setExtraData(array $args): array;
}
