<?php
namespace IT\Pacg\Responses;

/**
 * Class AccountTransactionsResponse
 * @package IT\Pacg\Responses
 */
class AccountTransactionsResponse extends PacgResponse
{
    /**
     * @var integer
     */
    public $transaction_receipt_id;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->transaction_receipt_id = $response_array['responseElements']['idRicevuta'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'transaction_receipt_id' => $this->transaction_receipt_id
        ];

        return parent::toArray($values);
    }
}