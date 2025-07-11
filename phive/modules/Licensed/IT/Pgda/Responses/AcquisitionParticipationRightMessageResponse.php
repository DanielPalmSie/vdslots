<?php
namespace IT\Pgda\Responses;

/**
 * Class AcquisitionParticipationRightMessageResponse
 * @package IT\Pgda\Responses
 */
class AcquisitionParticipationRightMessageResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | PARTICIPATION_CODE 16 bytes | YEAR  2 bytes | MOUTH  2 bytes | DAY  2 bytes
     * @var string
     */
    protected $format_success = 'H84header/ncode/A16participation_code/nyear/nmonth/nday';
}