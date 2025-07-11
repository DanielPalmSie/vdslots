<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\ListSelfExcludedAccountsResponse;

/**
 * Class ListSelfExcludedAccountsRequest
 * @package IT\Pacg\Requests
 */
class ListSelfExcludedAccountsV24Request extends PacgRequest
{
    protected $key = 'elencoContiAutoesclusi2In';
    protected $message_name = 'elencoContiAutoesclusi2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return ListSelfExcludedAccountsResponse::class;
    }
}