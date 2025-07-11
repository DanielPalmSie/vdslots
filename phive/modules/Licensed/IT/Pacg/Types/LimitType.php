<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure to represent the limits set to a gambling account
 * Class LimitType
 */
class LimitType extends AbstractEntity
{
    public $limit_type;
    public $amount;

    protected $fillable = [
        'limit_type',
        'amount',  // euro cents
    ];

    protected $rules = [
        'limit_type' => 'required|limit_type',
        'amount'     => 'required|integer',  // euro cents
    ];


    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "tipoLimite" => $this->limit_type,
            "importo"    => $this->amount
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Limit Type: {$this->limit_type()}\n" .
           "Amount: {$this->amount}";
    }
}