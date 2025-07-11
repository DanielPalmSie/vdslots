<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountBonusTransactionsResponse;

/**
 * Class AccountBonusTransactionsRequest
 */
class AccountBonusTransactionsRequest extends PacgRequest
{
    protected $key = 'movimentazioneBonusContoIn';
    protected $message_name = 'movimentazioneBonusConto';
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountBonusTransactionsResponse::class;
    }
}