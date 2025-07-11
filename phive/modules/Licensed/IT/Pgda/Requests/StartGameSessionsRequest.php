<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\StartGameSessionsResponse;

/**
 * Class StartGameSessionsRequest
 * @package IT\Pgda\Requests
 */
class StartGameSessionsRequest extends PgdaRequest
{
    protected $request_code = '400';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return StartGameSessionsResponse::class;
    }
}