<?php

namespace IT\Pacg\Requests;
use IT\Pacg\Responses\BonusReversalAccountTransactionsResponse;

class BonusReversalAccountTransactionsRequest extends PacgRequest
{
    protected $key = 'stornoMovimentazioneBonusContoIn';
    protected $message_name = 'stornoMovimentazioneBonusConto';
    protected $set_transaction_datetime = true; // this will set automatically dataOraSaldo in the request

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return BonusReversalAccountTransactionsResponse::class;
    }
}