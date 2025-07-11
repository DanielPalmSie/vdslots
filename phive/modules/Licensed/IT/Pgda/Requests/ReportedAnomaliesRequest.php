<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\ReportedAnomaliesResponse;

/**
 * Class ReportedAnomaliesRequest
 * @package IT\Pgda\Requests
 */
class ReportedAnomaliesRequest extends PgdaRequest
{
    protected $request_code = '560';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return ReportedAnomaliesResponse::class;
    }
}