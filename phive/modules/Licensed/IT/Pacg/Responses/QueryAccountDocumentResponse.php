<?php
namespace IT\Pacg\Responses;

use IT\Pacg\Types\ResponseDocumentType;

/**
 * Class QueryAccountDocumentResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountDocumentResponse extends PacgResponse
{
    /**
     * @var ResponseDocumentType
     */
    public $document;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        if (!empty($response_array['responseElements']['documento'])) {
            $this->document = (new ResponseDocumentType())->fill($response_array['responseElements']['documento']);
        }
    }

}