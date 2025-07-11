<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountMigrationResponse;

/**
 * Class AccountMigrationRequest
 * @package IT\Pacg\Requests
 */
class AccountMigrationRequest extends PacgRequest
{
    protected $key = 'migrazioneContoIn';
    protected $message_name = 'migrazioneConto';
    protected $set_account_information = false;
    protected $set_transaction_datetime = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountMigrationResponse::class;
    }
}
