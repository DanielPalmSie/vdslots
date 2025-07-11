<?php

namespace IT\Pacg\Services;


use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountTransactionsReversalEntity
 * @package IT\Pacg\Services
 */
class BonusReversalAccountTransactionsEntity extends PacgService
{
    public $account_code;
    public $bonus_receipt_id;
    public $transaction_reason;
    public $bonus_cancelation_amount;
    public $bonus_cancelation_type;
    public $balance_amount;
    public $bonus_balance_amount;
    public $bonus_details;
    public $bonus_balance_details;


    protected $fillable = [
        'account_code',
        'bonus_receipt_id',
        'transaction_reason',
        'bonus_cancelation_type',
        'bonus_cancelation_amount',
        'bonus_details',
        'balance_amount',
        'bonus_balance_amount',
        'bonus_balance_details',
    ];

    protected $rules = [
        'account_code' => 'required',
        'bonus_receipt_id' => 'required',
        'transaction_reason' => 'required|bonus_operation_reason_type',
        'bonus_cancelation_type' => 'required|bonus_cancelation_type',
        'bonus_details' => 'required',
        'balance_amount' => 'required',
        'bonus_balance_amount' => 'required',
        'bonus_balance_details' => 'required',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->bonus_balance_details)) {
            $this->bonus_balance_details = (new BonusDetailListType())->fill(['bonus_detail' => $this->bonus_balance_details]);
        }
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
            "codiceConto" => $this->account_code,
            "IDRicevutaBonus" => $this->bonus_receipt_id,
            "causaleMovimento" => $this->transaction_reason,
            "tipoStornoBonus" => $this->bonus_cancelation_type,
            "importoBonus" => $this->bonus_cancelation_amount,
            "numDettagliBonus" => empty($this->bonus_details) ? 0 : $this->bonus_details->getNumberOfBonuses(),
            "dettaglioBonus" => $this->bonus_details,
            "importoSaldo" => $this->balance_amount,
            "importoBonusSaldo" => $this->bonus_balance_amount,
            "numDettagliBonusSaldo" => empty($this->bonus_balance_details) ? 0 : $this->bonus_balance_details->getNumberOfBonuses()
        ];

        if (!empty($this->bonus_details)) {
            $values["dettaglioBonus"] = $this->bonus_details->toArray();
        }

        if (!empty($this->bonus_balance_details)) {
            $values["dettaglioBonusSaldo"] = $this->bonus_balance_details->toArray();
        }

        return parent::toArray($values);
    }
}