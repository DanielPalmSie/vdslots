<?php
namespace IT\Pgda\Responses;

/**
 * Class startGameSessionsResponse
 * @package IT\Pgda\Responses
 */
class StartGameSessionsResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | SESSION_ID 16 bytes |
     * @var string
     */
    protected $format_success = 'H84header/ncode/A*session_id';
}