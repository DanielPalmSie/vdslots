<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\OpenAccountLegalV26Response;

/**
 * Class OpenAccountLegalV26Request
 */
class OpenAccountLegalV26Request extends PacgRequest
{
    protected $key = 'aperturaContoPersonaGiuridica2In';
    protected $message_name = 'aperturaContoPersonaGiuridica2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return OpenAccountLegalV26Response::class;
    }
}
