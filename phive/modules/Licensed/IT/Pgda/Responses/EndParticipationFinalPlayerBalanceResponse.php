<?php
namespace IT\Pgda\Responses;

/**
 * Class EndParticipationFinalPlayerBalanceResponse
 * @package IT\Pgda\Responses
 */
class EndParticipationFinalPlayerBalanceResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | YEAR  2 bytes | MOUTH  2 bytes | DAY  2 bytes
     * @var string
     */
    protected $format_success = 'H84header/ncode/nyear/nmonth/nday';
}