<?php
namespace IT\Pacg\Services;

/**
 * Class SubregistrationEntity
 * @package IT\Pacg\Services
 */
class SubregistrationEntity extends PacgService
{
    public $account_code;
    public $balance_amount;
    public $balance_bonus_amount;

    protected $fillable = [
        'account_code',
        'balance_amount',
        'balance_bonus_amount'
    ];

    protected $rules = [
        'account_code' => 'required',
        'balance_amount' => 'required',
        'balance_bonus_amount' => 'required',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto" => $this->account_code,
            "importoSaldo" => $this->balance_amount,
            "importoBonusSaldo" => $this->balance_bonus_amount,
        ];

        return parent::toArray($values);
    }
}