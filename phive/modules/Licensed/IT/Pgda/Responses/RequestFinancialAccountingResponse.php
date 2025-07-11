<?php
namespace IT\Pgda\Responses;

use IT\Traits\BinaryTrait;

/**
 * Class RequestFinancialAccountingResponse
 * @package IT\Pgda\Responses
 */
class RequestFinancialAccountingResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | SESSION_TOTAL 2 bytes | BODY string
     * @var string
     */
    protected $format_success = 'H84header/ncode/Nitem_total';

    /**
     * @var array
     */
    private $sub_formats = "Cgame_type_code{id}/Ncode{id}/Nquantity{id}/Pamount{id}";

    /**
     * @inheritDoc
     */
    protected function extractResponse($response)
    {
        parent::extractResponse($response);
        if ($this->isSuccess() && !empty($this->response['item_total'])) {
            $total = $this->response['item_total'];
            for ($i = 0; $i < $total; $i++) {
                $this->format_success .= '/'. str_replace("{id}", $i , $this->sub_formats);
            }
            $this->response = BinaryTrait::deconvert($response, $this->format_success);
        }
        parent::extractResponse($response);
    }
}