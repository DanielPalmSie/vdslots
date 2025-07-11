<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountBalanceEntity
 * @package IT\AbstractRequest\Services
 */
class AccountBalanceEntity extends PacgService
{
    public $account_code;
    public $balance_amount;
    public $total_bonus_balance_on_account;
    public $transaction_datetime;
    public $bonus_details;

    protected $fillable = [
        'account_code',
        'balance_amount',
        'total_bonus_balance_on_account',
        'bonus_details'
    ];

    protected $rules = [
        'balance_amount' => 'required',
        'total_bonus_balance_on_account' => 'required',
        'account_code' => 'required',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);
        if (is_array($this->bonus_details)) {
            $this->bonus_details = (new BonusDetailListType())->fill(['bonus_detail' => $this->bonus_details]);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto"   => $this->account_code,
            "causaleMovimento"=> $this->transaction_reason,
            "importoMovimento"=> $this->balance_amount,
            "importoSaldo"=> $this->balance_amount,
            "importoBonusSaldo" => $this->total_bonus_balance_on_account,
            "numDettagliBonusSaldo" => empty($this->bonus_details) ? 0 : $this->bonus_details->getNumberOfBonuses(),
        ];

        if (!empty($this->bonus_details)) {
            $values["dettaglioBonusSaldo"] = $this->bonus_details->toArray();
        }

        return parent::toArray($values);
    }
}