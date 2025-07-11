<?php
namespace IT\Pgda\Responses;

use IT\Traits\BinaryTrait;

/**
 * Class ReportedAnomaliesResponse
 * @package IT\Pgda\Responses
 */
class ReportedAnomaliesResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | REQUEST_ID 8 bytes
     * @var string
     */
    protected $format_success = 'H84header/ncode/Jrequest_id';
}