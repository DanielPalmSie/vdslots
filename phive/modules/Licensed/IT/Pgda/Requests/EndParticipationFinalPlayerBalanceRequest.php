<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\EndParticipationFinalPlayerBalanceResponse;

/**
 * Class EndParticipationFinalPlayerBalanceRequest
 * @package IT\Pgda\Requests
 */
class EndParticipationFinalPlayerBalanceRequest extends PgdaRequest
{
    protected $request_code = '430';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return EndParticipationFinalPlayerBalanceResponse::class;
    }
}