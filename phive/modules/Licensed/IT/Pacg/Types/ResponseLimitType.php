<?php
namespace IT\Pacg\Types;


use IT\Abstractions\AbstractEntity;

/**
 * Structure to represent the limits set to a gambling account
 * Class ResponseLimitType
 */
class ResponseLimitType extends AbstractEntity
{
    protected $fillable = [
        'tipoLimite',
        'importo',  // euro cents
    ];

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "limit_type" => $this->tipoLimite,
            "amount"     => $this->importo
        ];
    }

}