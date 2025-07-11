<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdateEmailAccountResponse;

/**
 * Class UpdateEmailAccountRequest
 * @package IT\Pacg\Requests
 */
class UpdateEmailAccountRequest extends PacgRequest
{
    protected $key = 'aggiornaPostaElettronicaContoIn';
    protected $message_name = 'aggiornaPostaElettronicaConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdateEmailAccountResponse::class;
    }
}