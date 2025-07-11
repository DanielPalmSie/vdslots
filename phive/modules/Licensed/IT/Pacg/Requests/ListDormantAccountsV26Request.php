<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListDormantAccountsResponse;

/**
 * Class ListDormantAccountsV26Request
 * @package IT\Pacg\Requests
 */
class ListDormantAccountsV26Request extends PacgRequest
{
    protected $key = 'elencoContiDormienti2In';
    protected $message_name = 'elencoContiDormienti2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListDormantAccountsResponse::class;
    }
}
