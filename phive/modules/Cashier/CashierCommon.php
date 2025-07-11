<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;


require_once 'Mts.php';
require_once __DIR__ . '/../../traits/ObfuscateTrait.php';
require_once __DIR__ . '/Decorator/FallbackDepositDecorator.php';

class CashierCommon
{

    /**
     * A trait for obfuscating array values, which can be used to mask sensitive log data.
     */
    use ObfuscateTrait;

    /** @var DBUser $u_obj */
    public $u_obj;

    /** @var Mts $mts */
    public $mts;

    /** @var Cashier|CasinoCashier $cashier */
    public $cashier;

    /** @var WireCard $cc_handler */
    public $cc_handler;

    /** @var string $limits_supplier */
    public $limits_supplier;

    /** @var string $context */
    public $context;

    /** @var string $supplier */
    public $supplier;

    /** @var string $sub_supplier */
    public $sub_supplier;

    /** @var string $action */
    public $action;

    /** @var array $extra */
    public $extra;

    /** @var string $error_action */
    public $error_action;

    /** @var FallbackDepositDecorator */
    protected $fallbackDepositDecorator;

    public function init($context = 'phive', $u_obj = null)
    {

        // Typically we want to control things via arguments when things are invoked but in some cases
        // this is impossible and we must rely on the context setting.
        $this->context = $context;

        $this->cashier = phive('Cashier');
        $this->u_obj = $u_obj ?? cuPl();

        if (empty($this->u_obj)) {
            $this->u_obj = null;
            return 'err.nouser';
        }

        $this->mts = Mts::getInstance('', $this->u_obj);

        if ($context == 'phive') {
            if (!empty($_POST['lang'])) {
                phive("Localizer")->setLanguage($_POST['lang'], true);
            }

            // Move this out into the global context
            setCur($this->u_obj);
        }

        try {
            $depositFallbacks = $this->cashier->getSetting('fallback_deposit', []);
            $this->fallbackDepositDecorator = new FallbackDepositDecorator($this->u_obj, $depositFallbacks);
        } catch (Throwable $e) {
            phive('Logger')->getLogger('payments')->error($e->getMessage());
        }

        return true;
    }

    public function success($res)
    {
        if (isset($res['success'])) {
            return $res;
        }
        return ['success' => true, 'result' => $res];
    }

    public function successStop($res)
    {
        $this->stop($this->success($res));
    }

    public function fail($error, $translate = true, array $params = [])
    {
        // Override will always be respected.
        $translate = $error['translate'] ?? $translate;

        if (!empty($this->error_action)) {
            return [
                'success' => false,
                'errors' => ['action' => $this->error_action],
                'params' => $params
            ];
        }

        if (isset($error['success'])) {
            // We already have a fail array but we might want to translate the errors this time (typically wanted in a phive context).
            if ($error['success'] === false && is_array($error['errors']) && $translate) {
                // We want to translate multiple errors.
                $str = '';
                foreach ($error['errors'] as $loc_str) {
                    $translated_content = t($loc_str);
                    // If the error string was not translatable we simply use the actual error string as that is more
                    // informative than returning an empty error string, ie better than nothing.
                    $str .= ' ' . (empty($translated_content) ? $loc_str : $translated_content);
                }
                $error['errors'] = $str;
            }
            return $error;
        }

        if (is_array($error)) {
            $raw = $error['errors'] ?? [];
            if ($translate) {
                // We have multiple errors and we want to translate so we concatenate the translations into a single string.
                $str = '';
                foreach ($error['errors'] as $loc_str) {
                    $str .= ' ' . t($loc_str);
                }
                $error = $str;
            }


            // In case we have an array of errors but do now wish to translate we do nothing.
            // This is the typical Laravel scenario.
        } else {
            $raw = [$error];
            $error = $translate ? t($error) : $error;
        }

        $result = ['success' => false, 'errors' => $error];
        if ($this->fallbackDepositDecorator) {
            return $this->fallbackDepositDecorator->decorate($result, $raw);
        }

        return $result;
    }

    /**
     * @param array $error
     * @param bool $translate
     * @param bool $return
     *
     * @return array|void
     */
    public function failStop($error, bool $translate = true, bool $return = false)
    {
        $this->cashier->fireOnFailedDeposit($this->u_obj, $this->action);

        return $this->stop($this->fail($error, $translate, $error['params'] ?? []), $return);
    }

    /**
     * @param array $arr
     * @param bool $return
     *
     * @return array|void
     */
    public function stop($arr, bool $return = false)
    {
        if ($return) {
            return $arr;
        }

        die(json_encode($arr));
    }

    public function fixCardNumber($number)
    {
        return preg_replace('/\D/', '', $number);
    }

    public function cardInit($action)
    {
        $this->cc_handler = phive('WireCard');
        $this->action = $action;

        if ($this->context == 'phive') {
            $_POST['cardnumber'] = $this->fixCardNumber($_POST['cardnumber']);
        }

        return $this;
    }

    public function skrillCommon($args)
    {
        $skrill_email = cuSetting('mb_email');
        $skrill_email = empty($skrill_email) ? trim($args['email']) : $skrill_email;

        if (empty($skrill_email) && $this->sub_supplier != 'skrill') {
            $skrill_email = $this->u_obj->getAttr('email');
        }

        if (empty($skrill_email)) {
            return $this->fail('err.empty', false);
        }

        // According to Skrill the sub options only need an email because they want to be able to contact the players if something goes wrong.
        // We therfore skip validations on it.
        if (
            $this->sub_supplier == 'skrill'
            && phive('Cashier')->hasDuplicateAccountUsage($this->u_obj->getId(), 'skrill', $skrill_email)
        ) {
            return $this->fail('err.duplicate', false);
        }

        return $skrill_email;
    }

    public function handleMuchbetterOneToOneRelationship(DBUser $user, string $mobile): bool
    {
        $mobile_is_used_by_another_user = true;

        //if user mobile is associated with other users block it.
        $sql = "SELECT `value`, `user_id` FROM users_settings WHERE setting = 'muchbetter_mobile' AND value = '{$mobile}' AND user_id <> {$user->userId}";
        $mm = phive('SQL')->shs()->loadAssoc($sql);
        if ($mm) {
            $mobile_is_used_by_another_user = false;
        }
        if (!$mobile_is_used_by_another_user) {
            $first_owner = $mm['user_id'] ?? NULL;
            $this->cashier->logOneToOneRelationshipViolationAction($this->u_obj, 'muchbetter', $mobile, $first_owner);
        }
        return $mobile_is_used_by_another_user;
    }

    /**
     * Check if the mobile number entered by the user is different than the one associated to the Muchbetter account
     *
     * @param DBUser $user
     * @param string $muchbetter_mobile Mobile number entered by the user
     * @return boolean (false: The mobile number is different than the one coresponding to the Muchbetter account, true: The entered mobile number is the same)
     *
     */
    public function muchbetterPhoneCheck(DBUser $user, $muchbetter_mobile)
    {
        $muchbetter_acc_no = $user->getSetting('muchbetter_mobile');
        if (!empty($muchbetter_acc_no)) {
            return $muchbetter_acc_no === $muchbetter_mobile;
        } else {
            return true;
        }
    }

    public function checkAmount($amount)
    {

        $amount = $this->cashier->cleanUpNumber($amount);

        if (get_class($this) == 'WithdrawStart') {
            $amount = $this->cashier->handleDepBonuses($_SESSION['mg_id'], $amount * 100) / 100;
        }

        if (empty($amount)) {
            return 'err.empty';
        }

        return $amount;
    }

    public function loadPspFile()
    {
        if ($this->context == 'phive') {
            $load_file = __DIR__ . "/" . ucfirst($this->supplier) . ".php";
            if (file_exists($load_file)) {
                require_once($load_file);
            }
        }

    }

    public function defaultInit($action, $args)
    {
        if ($this->context == 'phive') {
            // TODO remove this when we have successfully switched to the MTS
            if ($args['supplier'] == 'instadebit' && phive('Cashier')->getSetting('instadebit_via_mts') !== true) {
                require_once __DIR__ . '/html/instadebit_start.php';
                exit;
            }
        }

        $this->action = $action;

        $this->extra = [
            'nid' => $this->u_obj->getNid(),
            'site' => phive()->getSetting('domain'),
            'site_display_name' => phive()->getSetting('domain')
        ];

        $config = phiveApp(PspConfigServiceInterface::class)->getPspSetting($args['supplier']);

        // Logo for override in case the same method / PSP has more than one logo, ex: Klarna and Sofort.
        $supplier = $config['logo_for'] ?? $args['supplier'];

        if (array_key_exists('option_of', $config)) {
            $supplier = $config['option_of'];
        }

        // We disregard $args['network'] as we can't trust user controlled data anyway, we don't want people
        // to be able to route via a different network than has been configured.
        $this->network = $this->cashier->getPspRoute($this->u_obj, $supplier);

        // Resolve network for Apple Pay or Google Pay
        if ($this->network === 'applepay' || $this->network === 'googlepay') {
            $providers = phiveApp(PspConfigServiceInterface::class)->getPspSetting($this->network, 'providers');
            $this->network = $this->mts->getAppleOrGooglepayProviderBySequences($providers);
            if (is_null($this->network)) {
                $ccSupplier = $this->mts->getCcSupplier('deposit', '', null, ['cc_psps' => $providers]);
                if (!empty($ccSupplier)) {
                    $this->network = $ccSupplier;
                }
            }
        }

        $this->supplier = $this->network ?? $args['supplier'];
        $this->sub_supplier = $supplier;

        $this->mts->setSupplier($this->supplier);
        $this->mts->setSubSupplier($this->sub_supplier);

        $this->limits_supplier = $this->sub_supplier ?? $this->supplier;

        return $this;
    }
}
