<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountLimitResponse;

/**
 * Class QueryAccountLimitRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountLimitRequest extends PacgRequest
{
    protected $key = 'interrogazioneLimitiIn';
    protected $message_name = 'interrogazioneLimiti';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountLimitResponse::class;
    }
}