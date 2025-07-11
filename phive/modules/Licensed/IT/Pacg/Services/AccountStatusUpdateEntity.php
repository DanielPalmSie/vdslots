<?php
namespace IT\Pacg\Services;

/**
 * Class AccountStatusUpdateEntity
 * @package IT\AbstractRequest\Services
 */
class AccountStatusUpdateEntity extends AbstractService
{
    /**
     * Unique Gambling Account Code for the CN
     * @var
     */
    protected $account_code;
    protected $transaction_amount;
    protected $change_reason;
    protected $account_network_id;
    protected $account_status;



    /**
     * @return mixed
     */
    public function getAccountStatus()
    {
        return $this->account_status;
    }

    /**
     * @param mixed $account_status
     */
    public function setAccountStatus($account_status)
    {
        $this->account_status = $account_status;
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
    public function getChangeReason()
    {
        return $this->change_reason;
    }

    /**
     * @param mixed $transaction_reason
     */
    public function setChangeReason($change_reason)
    {
        $this->change_reason = $change_reason;
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
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            "idReteConto"=> $this->getAccountSalesNetworkId(),
            "idTransazione" => $this->getTransactionId(),
            "idCnConto"=> $this->getAccountNetworkId(),
            "codiceConto"=> $this->getAccountCode(),
            "stato"=>$this->getAccountStatus(),
            "casuale"=> $this->getChangeReason()
        ];

        //print_r($values); die;
        return parent::toArray($values);
    }

}