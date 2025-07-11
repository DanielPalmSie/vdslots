<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\SessionReportedAnomaliesResponse;

/**
 * Class SessionReportedAnomaliesRequest
 * @package IT\Pgda\Requests
 */
class SessionReportedAnomaliesRequest extends PgdaRequest
{
    protected $request_code = '565';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return SessionReportedAnomaliesResponse::class;
    }
}