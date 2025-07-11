<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListDormantAccountsResponse;

/**
 * Class ListDormantAccountsRequest
 * @package IT\Pacg\Requests
 */
class ListDormantAccountsRequest extends PacgRequest
{
    protected $key = 'elencoContiDormientiIn';
    protected $message_name = 'elencoContiDormienti';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListDormantAccountsResponse::class;
    }
}