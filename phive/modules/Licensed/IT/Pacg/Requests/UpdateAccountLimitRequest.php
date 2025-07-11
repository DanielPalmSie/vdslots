<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdateAccountLimitResponse;

/**
 * Class UpdateAccountLimitRequest
 * @package IT\Pacg\Requests
 */
class UpdateAccountLimitRequest extends PacgRequest
{
    protected $key = 'aggiornaLimiteIn';
    protected $message_name = 'aggiornaLimite';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdateAccountLimitResponse::class;
    }
}