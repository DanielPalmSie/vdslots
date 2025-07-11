<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of Bonus detail
 * Class BonusDetailType
 */
class BonusDetailType extends AbstractEntity
{
    public $gaming_family;
    public $gaming_type;
    public $bonus_amount;

    protected $fillable = [
        'gaming_family',
        'gaming_type',
        'bonus_amount',
    ];

    protected $rules = [        
        'gaming_family' => 'required|gaming_family',
        'gaming_type'   => 'required|integer',
        'bonus_amount'  => 'required|integer',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "famigliaGioco" => $this->gaming_family,
            "tipoGioco"     => $this->gaming_type,
            "importo"       => $this->bonus_amount
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Family: {$this->gaming_family}\n" .
           "Type: {$this->gaming_type}\n" .
           "Amount: {$this->bonus_amount}";
    }
}