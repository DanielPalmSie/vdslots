<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListSelfExcludedAccountsResponse;

/**
 * Class ListSelfExcludedAccountsRequest
 * @package IT\Pacg\Requests
 */
class ListSelfExcludedAccountsRequest extends PacgRequest
{
    protected $key = 'elencoContiAutoesclusiIn';
    protected $message_name = 'elencoContiAutoesclusi';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListSelfExcludedAccountsResponse::class;
    }
}