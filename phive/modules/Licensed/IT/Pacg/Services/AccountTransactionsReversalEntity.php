<?php
namespace IT\Pacg\Services;


use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountTransactionsReversalEntity
 * @package IT\Pacg\Services
 */
class AccountTransactionsReversalEntity extends PacgService
{
    public $account_code;
    public $transaction_receipt_id;
    public $payment_method;
    public $transaction_description;
    public $reversal_type;
    public $balance_amount;
    public $transaction_amount;
    public $balance_bonus_amount;
    public $balance_bonus_detail;
    public $datetime;

    protected $fillable = [
        'account_code',
        'transaction_receipt_id',
        'payment_method',
        'transaction_description',
        'reversal_type',
        'balance_amount',
        'transaction_amount',
        'balance_bonus_amount',
        'balance_bonus_detail',
    ];

    protected $rules = [
        'account_code' => 'required',
        'transaction_receipt_id' => 'required',
        'payment_method' => 'required',
        'transaction_description' => 'required',
        'reversal_type' => 'required',
        'balance_amount' => 'required',
        'transaction_amount' => 'required',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->balance_bonus_detail)) {
            $this->balance_bonus_detail = (new BonusDetailListType())->fill(['bonus_detail' => $this->balance_bonus_detail]);
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
            "idMovDaStornare" => $this->transaction_receipt_id,
            "mezzoDiPagamento" => $this->payment_method,
            "causaleMovimento" => $this->transaction_description,
            "tipoStorno" => $this->reversal_type,
            "importoMovimento" => $this->transaction_amount,
            "importoSaldo" => $this->balance_amount,
            "importoBonusSaldo" => $this->balance_bonus_amount,
            "numDettagliBonusSaldo" => empty($this->balance_bonus_detail) ? 0 : $this->balance_bonus_detail->getNumberOfBonuses()
        ];

        if (! empty($this->balance_bonus_detail)) {
            $values["dettaglioBonusSaldo"] = $this->balance_bonus_detail->toArray();
        }


        return parent::toArray($values);
    }
}