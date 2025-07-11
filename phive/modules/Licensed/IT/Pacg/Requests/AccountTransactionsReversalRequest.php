<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountTransactionsReversalResponse;

/**
 * Class AccountTransactionsReversalRequest
 * @package IT\Pacg\Requests
 */
class AccountTransactionsReversalRequest extends PacgRequest
{
    protected $key = 'stornoMovimentazioneContoIn';
    protected $message_name = 'stornoMovimentazioneConto';
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountTransactionsReversalResponse::class;
    }
}