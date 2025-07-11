<?php
namespace IT\Pacg\Responses;

use IT\Abstractions\AbstractResponse;
use IT\Pacg\Codes\ReturnCode;

/**
 * Class PacgResponse
 * @package IT\Pacg\Responses
 */
class PacgResponse extends AbstractResponse
{

    const RESPONSE_CODE_NAME = 'esitoRichiesta';

    /**
     * @return ReturnCode
     */
    protected function getNewReturnCode(): ReturnCode
    {
        return new ReturnCode();
    }

    /**
     * @param \stdClass $response
     * @return array
     */
    private static function stdClassToArray(\stdClass $response): array
    {
        return json_decode(json_encode($response), true);
    }

    /**
     * @param \stdClass $response
     */
    protected function extractResponse($response)
    {
        $response_array = self::stdClassToArray($response);
        $this->response = $response_array['responseElements'];
        $this->code = $this->response[self::RESPONSE_CODE_NAME];
        $this->message = $this->return_code->getCodeDescription($this->code, [], $this->getLanguage());

        $this->fillableResponse($response_array);
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return ReturnCode::SUCCESS_CODE == $this->getCode();
    }

    /**
     * @return void
     */
    public function fillableResponse($response_array)
    {

    }
}