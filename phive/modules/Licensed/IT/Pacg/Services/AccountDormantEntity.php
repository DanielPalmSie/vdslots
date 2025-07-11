<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\DateType;

/**
 * Class AccountDormantEntity
 * @package IT\Pacg\Services
 */
class AccountDormantEntity extends PacgService
{
    public $account_code;
    public $date_dormant;
    public $balance_amount;

    protected $fillable = [
        'account_code',
        'date_dormant',
        'balance_amount'
    ];

    protected $rules = [
        'account_code' => 'required',
        'date_dormant' => 'required|array',
        'balance_amount' => 'required',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->date_dormant)) {
            $this->date_dormant = (new DateType())->fill($this->date_dormant);
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
            "dataDormiente" => $this->date_dormant->toArray(),
            "importoSaldo"  => $this->balance_amount,
        ];

        return parent::toArray($values);
    }
}