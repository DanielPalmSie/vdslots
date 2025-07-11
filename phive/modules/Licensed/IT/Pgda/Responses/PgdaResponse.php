<?php
namespace IT\Pgda\Responses;

use IT\Abstractions\AbstractResponse;
use IT\Pgda\Codes\ReturnCode;
use IT\Traits\BinaryTrait;

class PgdaResponse extends AbstractResponse
{
    /**
     * @var string
     */
    protected $format = 'H84header/ncode';

    /**
     * @var string
     */
    protected $format_success = '';

    /**
     * @inheritDoc
     */
    protected function getNewReturnCode(): ReturnCode
    {
        return new ReturnCode();
    }

    /**
     * @return string
     */
    protected function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @inheritDoc
     */
    protected function extractResponse($response)
    {
        if (licSetting('mock_adm')) {
            $this->response = json_decode($response, 1);
            $this->code = $this->response['code'];
            $code = 1;
        } else {
            $deconvertedResponse = BinaryTrait::deconvert($response, $this->getFormat());
            $this->response = is_array($deconvertedResponse) ? $deconvertedResponse : [];
            $this->code = $this->response['code'] ?? null;
            $code = $this->code;

        if (is_null($this->code)) {
            $this->message = $response;
            return;
        }
            if (empty($code)) {
                if (!empty($this->format_success)) {
                    $this->response = BinaryTrait::deconvert($response, $this->format_success);
                }
                $code = (int)$this->isSuccess();
            }
        }

        $this->message = $this->return_code->getCodeDescription($code, [], $this->getLanguage());
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return ReturnCode::SUCCESS_CODE == $this->getCode();
    }
}
