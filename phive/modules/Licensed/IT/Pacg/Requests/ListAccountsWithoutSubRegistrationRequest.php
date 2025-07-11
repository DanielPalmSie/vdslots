<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListAccountsWithoutSubRegistrationResponse;

/**
 * Class ListAccountsWithoutSubRegistrationRequest
 * @package IT\Pacg\Requests
 */
class ListAccountsWithoutSubRegistrationRequest extends PacgRequest
{
    protected $key = 'elencoContiSenzaSubregistrazioneIn';
    protected $message_name = 'elencoContiSenzaSubregistrazione';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListAccountsWithoutSubRegistrationResponse::class;
    }
}