<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\AcquisitionParticipationRightMessageResponse;

/**
 * Class AcquisitionParticipationRightMessageRequest
 * @package IT\Pgda\Requests
 */
class AcquisitionParticipationRightMessageRequest extends PgdaRequest
{
    protected $request_code = '420';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return AcquisitionParticipationRightMessageResponse::class;
    }
}