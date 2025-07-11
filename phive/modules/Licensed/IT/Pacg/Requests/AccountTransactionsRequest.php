<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountTransactionsResponse;

/**
 * Class AccountTransactionsRequest
 */
class AccountTransactionsRequest extends PacgRequest
{
    protected $key = 'movimentazioneContoIn';
    protected $message_name = 'movimentazioneConto';
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountTransactionsResponse::class;
    }
}