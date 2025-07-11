<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountBalanceResponse;

/**
 * Class AccountBalanceRequest
 * @package IT\PacgRequest\Requests
 */
class AccountBalanceRequest extends PacgRequest
{
    protected $key = 'saldoContoIn';
    protected $message_name = 'saldoConto';
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountBalanceResponse::class;
    }
}