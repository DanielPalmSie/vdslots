<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListAccountsWithoutSubRegistrationResponse;

/**
 * Class ListAccountsWithoutSubRegistrationV26Request
 * @package IT\Pacg\Requests
 */
class ListAccountsWithoutSubRegistrationV26Request extends PacgRequest
{
    protected $key = 'elencoContiSenzaSubregistrazione2In';
    protected $message_name = 'elencoContiSenzaSubregistrazione2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListAccountsWithoutSubRegistrationResponse::class;
    }
}
