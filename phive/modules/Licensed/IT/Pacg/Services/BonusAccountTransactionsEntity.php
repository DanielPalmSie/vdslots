<?php
namespace IT\Pacg\Services;

use IT\Pacg\Types\BonusDetailType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class BonusAccountTransactionsEntity
 * @package IT\AbstractRequest\Services
 */
class BonusAccountTransactionsEntity extends AbstractService
{
    /**
     * Unique Gambling Account Code for the CN
     * @var
     */
    protected $account_code;
    protected $payment_method;
    protected $number_bonus_details;
    protected $bonus_balance_share_amount;
    protected $bonus_balance_amount;
    protected $balance_amount;
    protected $transaction_amount;
    protected $transaction_reason;
    protected $transaction_datetime;
    protected $account_sales_network_id;
    protected $bonus_details;
    protected $number_bonus_balance_details;
    protected $bonus_balance_details;

    /**
     * @return mixed
     */
    public function getBonusBalanceAmount()
    {
        return $this->bonus_balance_amount;
    }

    /**
     * @param mixed $bonus_balance_amount
     */
    public function setBonusBalanceAmount($bonus_balance_amount)
    {
        $this->bonus_balance_amount = $bonus_balance_amount;
    }

    /**
     * @return mixed
     */
    public function getBonusDetails():BonusDetailType
    {
        return $this->bonus_details;
    }

    /**
     * @param mixed $bonus_details
     */
    public function setBonusDetails(BonusDetailType $bonus_details)
    {
        $this->bonus_details = $bonus_details;
    }

    /**
     * @return mixed
     */
    public function getBonusBalanceDetails():BonusDetailType
    {
        return $this->bonus_balance_details;
    }

    /**
     * @param mixed $bonus_details
     */
    public function setBonusBalanceDetails(BonusDetailType $bonus_balance_details)
    {
        $this->bonus_balance_details = $bonus_balance_details;
    }

    /**
     * @return mixed
     */
    public function getAccountSalesNetworkId()
    {
        return $this->account_sales_network_id;
    }

    /**
     * @param mixed $account_sales_network_id
     */
    public function setAccountSalesNetworkId($account_sales_network_id)
    {
        $this->account_sales_network_id = $account_sales_network_id;
    }

    /**
     * @return mixed
     */
    public function getAccountNetworkId()
    {
        return $this->account_network_id;
    }

    /**
     * @param mixed $account_network_id
     */
    public function setAccountNetworkId($account_network_id)
    {
        $this->account_network_id = $account_network_id;
    }
    protected $account_network_id;

    /**
     * @return mixed
     */
    public function getBalanceAmount()
    {
        return $this->balance_amount;
    }

    /**
     * @param mixed $balance_amount
     */
    public function setBalanceAmount($balance_amount)
    {
        $this->balance_amount = $balance_amount;
    }

    /**
     * @return mixed
     */
    public function getTransactionAmount()
    {
        return $this->transaction_amount;
    }

    /**
     * @param mixed $transaction_amount
     */
    public function setTransactionAmount($transaction_amount)
    {
        $this->transaction_amount = $transaction_amount;
    }

    /**
     * @return mixed
     */
    public function getTransactionReason()
    {
        return $this->transaction_reason;
    }

    /**
     * @param mixed $transaction_reason
     */
    public function setTransactionReason($transaction_reason)
    {
        $this->transaction_reason = $transaction_reason;
    }

    /**
     * @return mixed
     */
    public function getBonusBalanceShareAmount()
    {
        return $this->bonus_balance_share_amount;
    }

    /**
     * @param mixed $bonus_balance_share_amount
     */
    public function setBonusBalanceShareAmount($bonus_balance_share_amount)
    {
        $this->bonus_balance_share_amount = $bonus_balance_share_amount;
    }

    /**
     * @return mixed
     */
    public function getNumberBonusBalanceDetails()
    {
        return $this->number_bonus_balance_details;
    }

    /**
     * @param $number_bonus_balance_details
     */
    public function setNumberBonusBalanceDetails($number_bonus_balance_details)
    {
        $this->number_bonus_balance_details = $number_bonus_balance_details;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * @param mixed $payment_method
     */
    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;
    }


    /**
     * @return string
     */
    public function getAccountCode(): string
    {
        return $this->account_code;
    }

    /**
     * @param string $account_code
     */
    public function setAccountCode(string $account_code)
    {
        $this->account_code = $account_code;
    }

    /**
     * @return mixed
     */
    public function getTransactionDateTime():DateTimeType
    {
        return $this->transaction_datetime;
    }

    /**
     * @param DateTimeType $transaction_datetime
     */
    public function setTransactionDateTime(DateTimeType $transaction_datetime)
    {
        $this->transaction_datetime = $transaction_datetime;
    }

    /**
     * @return mixed
     */
    public function getNumberBonusDetails()
    {
        return $this->number_bonus_details;
    }

    /**
     * @param mixed $number_bonus_details
     */
    public function setNumberBonusDetails($number_bonus_details)
    {
        $this->number_bonus_details = $number_bonus_details;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            "idReteConto"=> $this->getAccountSalesNetworkId(),
            "idCnConto"=> $this->getAccountNetworkId(),
            "causaleMovimento"=> $this->getTransactionReason(),
            "importoSaldo"=> $this->getBalanceAmount(),
            "importoBonus" => $this->getBonusBalanceAmount(),
            "codiceConto" => $this->getAccountCode(),
            "idTransazione" => $this->getTransactionId(),
            "dataOraSaldo" => $this->getTransactionDateTime()->toArray(),
            "importoBonusSaldo" => $this->getBonusBalanceShareAmount(),
            "numDettagliBonus" => $this->getNumberBonusDetails(),
            "dettaglioBonus"=>$this->getBonusDetails()->toArray(),
            "numDettagliBonusSaldo" => $this->getNumberBonusBalanceDetails(),
            "dettaglioBonusSaldo"=>$this->getBonusBalanceDetails()->toArray(),
        ];

        return parent::toArray($values);
    }

}