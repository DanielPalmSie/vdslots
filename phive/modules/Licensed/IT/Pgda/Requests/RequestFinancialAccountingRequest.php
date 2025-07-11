<?php
namespace IT\Pgda\Requests;

use IT\Pgda\Responses\RequestFinancialAccountingResponse;

/**
 * Class RequestFinancialAccountingRequest
 * @package IT\Pgda\Requests
 */
class RequestFinancialAccountingRequest extends PgdaRequest
{
    protected $request_code = '800';

    /**
     * @return string
     */
    public function responseName(): string
    {
        return RequestFinancialAccountingResponse::class;
    }
}