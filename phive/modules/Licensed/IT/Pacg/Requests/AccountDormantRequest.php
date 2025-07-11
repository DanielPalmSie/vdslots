<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\AccountDormantResponse;

/**
 * Class AccountDormantRequest
 * @package IT\Pacg\Requests
 */
class AccountDormantRequest extends PacgRequest
{
    protected $key = 'contoDormienteIn';
    protected $message_name = 'contoDormiente';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return AccountDormantResponse::class;
    }

}