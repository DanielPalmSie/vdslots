<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\OpenAccountLegalResponse;

/**
 * Class OpenAccountLegalRequest
 */
class OpenAccountLegalRequest extends PacgRequest
{
    protected $key = 'aperturaContoPersonaGiuridicaIn';
    protected $message_name = 'aperturaContoPersonaGiuridica';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return OpenAccountLegalResponse::class;
    }
}