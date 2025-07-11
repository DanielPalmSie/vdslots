<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountBonusTransactionsResponse;

/**
 * Class AccountBonusTransactionsRequest
 */
class AccountBonusTransactionsV24Request extends PacgRequest
{
    protected $key = 'movimentazioneBonusConto2In';
    protected $message_name = 'movimentazioneBonusConto2';
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountBonusTransactionsResponse::class;
    }
}