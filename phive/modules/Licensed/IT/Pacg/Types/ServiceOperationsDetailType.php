<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class ServiceOperationsDetailType
 * @package IT\Pacg\Types
 */
class ServiceOperationsDetailType extends AbstractEntity
{
    public $service_reason;
    public $number_of_transactions;

    protected $fillable = [
        'service_reason',
        'number_of_transactions',
    ];

    protected $rules = [
        'service_reason'          => 'required|service_operation_reason_code',
        'number_of_transactions'  => 'required|integer',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [

        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Reason: {$this->service_reason}\n" .
           "Number of Transactions: {$this->number_of_transactions}";
    }
}