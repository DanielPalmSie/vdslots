<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountTransactionsEntity
 * @package IT\AbstractRequest\Services
 */
class AccountTransactionsEntity extends PacgService
{
    public $account_network_id;
    public $account_code;
    public $payment_method;
    public $total_bonus_balance_on_account;
    public $balance_amount;
    public $transaction_amount;
    public $transaction_reason;
    public $bonus_details;

    protected $fillable = [
        'account_code',
        'payment_method',
        'total_bonus_balance_on_account',
        'balance_amount',
        'transaction_amount',
        'transaction_reason',
        'bonus_details'
    ];

    protected $rules = [
        'account_code' => 'required',
        'payment_method' => 'required',
        'total_bonus_balance_on_account' => 'required',
        'balance_amount' => 'required',
        'transaction_amount' => 'required',
        'transaction_reason' => 'required',
        'bonus_details' => 'array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->bonus_details)) {
            $this->bonus_details = (new BonusDetailListType())->fill(['bonus_detail' => $this->bonus_details]);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "causaleMovimento" => $this->transaction_reason,
            "importoMovimento" => $this->transaction_amount,
            "importoSaldo" => $this->balance_amount,
            "mezzoDiPagamento" => $this->payment_method,
            "importoBonusSaldo" => $this->total_bonus_balance_on_account,
            "numDettagliBonusSaldo" => empty($this->bonus_details) ? 0 : $this->bonus_details->getNumberOfBonuses(),
            "codiceConto" => $this->account_code,
        ];

        if(!empty($this->bonus_details)) {
            $values["dettaglioBonusSaldo"] = $this->bonus_details->toArray();
        }

        return parent::toArray($values);
    }
}