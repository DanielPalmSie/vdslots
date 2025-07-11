<?php


trait TaxTrait
{
    /**
     * We check if we should charge tax on a bet.
     *
     * @return bool
     */
    public function hasTaxOnBet()
    {
        return !empty($this->getLicSetting('tax_on_bet'));
    }

    /**
     *
     * @param DBUser $user
     * @param mixed $amount
     * @param int $type It can be for example 7 for a refund
     * @return bool
     */
    public function deductBetTaxAmount($user, $amount, $cash_balance, $type = 0)
    {
        if (empty($user) || empty($amount)) {
            return false;
        }

        $percentage = $this->getLicSetting('tax_on_bet');

        $tax = max(abs($amount) * $percentage, 1);

        $tax = round($tax);

        /** @var Casino $casino */
        $casino = phive('Casino');

        if ($type == 7) {
            $tax_amount = $tax;
            $type = 105;
        } else {
            $tax_amount = $tax * -1;
            $type = 104;
            if (($cash_balance - abs($amount) - abs($tax)) < 0) {
                return false;
            }
        }

        return $casino->changeBalance($user, $tax_amount, 'Turnover tax on wager', $type);
    }
}