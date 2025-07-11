<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class TransactionHandlingDetailType
 * @package IT\Pacg\Types
 */
class TransactionHandlingDetailType extends AbstractEntity
{
    public $transaction_reason;
    public $number_of_transactions;
    public $total_amount;

    protected $fillable = [
        'transaction_reason',
        'number_of_transactions',
        'total_amount',
    ];

    protected $rules = [
        'transaction_reason'     => 'required|in:transaction_reason_code',
        'number_of_transactions' => 'required|integer',
        'total_amount'           => 'required|integer',  // euro cents
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
           "Reason: {$this->getTransactionReason()}\n" .
           "Number of Transactions: {$this->getNumberOfTransactions()}\n" .
           "Total: {$this->getTotalAmount()}";
    }
}