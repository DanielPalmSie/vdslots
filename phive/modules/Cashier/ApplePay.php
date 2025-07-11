<?php

require_once __DIR__ . '/Payment.php';

/**
 * Class ApplePay
 *
 * For now this class is only use from laraphive/syx-payments-api to provide ApplePay on the mobile app trough the API
 *
 * @api
 */
class ApplePay extends Payment
{
    /**
     * @param array $args
     *
     * @return array|false
     */
    public function deposit(array $args)
    {
        $user = cu();

        if (count($user->getBonusesToForfeitBeforeDeposit())) {
            $this->setError('forfeit.deposit.blocked.error');

            return false;
        }

        $this->mts->setDepositType($args['deposit_type'], $args['parent_id']);

        return $this->defaultInit($this->action, $args)->execute($args);
    }

    /**
     * @param array $args
     *
     * @return array
     */
    protected function setExtraData(array $args): array
    {
        $extra = array_merge($this->extra, $this->mts->getUrls($this->u_obj, $this->action));
        if ($this->sub_supplier == 'applepay') {
            $extra['token'] = $args['token'];
        }

        return $extra;
    }
}