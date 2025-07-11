<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountBonusTransactionsEntity
 * @package IT\AbstractRequest\Services
 */
class AccountBonusTransactionsEntity extends PacgService
{
    public $account_code;
    public $payment_method;
    public $bonus_balance_amount;
    public $balance_amount;
    public $transaction_amount;
    public $transaction_reason;
    public $bonus_details;
    public $bonus_balance_details;

    /**
     * The total bonus amount on the game account including what is being sent.
     * (Renamed from 'bonus_balance_share_amount')
     *
     * (importoBonusSaldo)
     *
     * @var
     */
    public $total_bonus_balance_on_account;

    /**
     * The number of total bonus details on the game account including what is being sent.
     * Each bonus_detail will contain the total bonus amount for one gaming_type/gaming_family combination.
     *
     * (numDettagliBonusSaldo)
     *
     * @var
     */
    public $number_of_bonus_details_on_account;

    /**
     * @var array
     */
    protected $fillable = [
        'account_code',
        'total_bonus_balance_on_account',
        'bonus_balance_amount',
        'balance_amount',
        'transaction_amount',
        'transaction_reason',
        'bonus_details',
        'bonus_balance_details'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected $rules = [
        'account_code'                   => 'required', // Our user_id's are integers, but Sogei accepts strings, and expects strings during the audit
        'total_bonus_balance_on_account' => 'required|integer',
        'bonus_balance_amount'           => 'required|integer',
        'balance_amount'                 => 'required|integer',
        'transaction_amount'             => 'required|integer',
        'transaction_reason'             => 'required|integer',
        'bonus_details'                  => 'required|array',
        'bonus_balance_details'          => 'required|array',
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
        if (!empty($this->bonus_balance_details) && is_array($this->bonus_balance_details)) {
            $this->bonus_balance_details = (new BonusDetailListType())->fill(['bonus_detail' => $this->bonus_balance_details]);
        }

        $this->number_of_bonus_details_on_account = empty($this->bonus_balance_details) ? 0 : $this->bonus_balance_details->getNumberOfBonuses();

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "causaleMovimento"      => $this->transaction_reason,
            "importoSaldo"          => $this->balance_amount,
            "importoBonus"          => $this->bonus_balance_amount,
            "codiceConto"           => $this->account_code,
            "importoBonusSaldo"     => $this->total_bonus_balance_on_account,
            "numDettagliBonus"      => $this->bonus_details->getNumberOfBonuses(),
            "dettaglioBonus"        => $this->bonus_details->toArray(),
            "numDettagliBonusSaldo" => $this->number_of_bonus_details_on_account,
        ];

        if (!empty($this->bonus_balance_details)) {
            $values["dettaglioBonusSaldo"] = $this->bonus_balance_details->toArray();
        }

        return parent::toArray($values);
    }
}