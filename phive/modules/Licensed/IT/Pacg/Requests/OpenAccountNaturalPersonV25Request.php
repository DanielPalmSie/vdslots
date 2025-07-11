<?php

namespace IT\Pacg\Requests;

use IT\Pacg\Responses\OpenAccountNaturalPersonV25Response;

/**
 * Class OpenAccountNaturalPersonRequest
 * @package IT\Pacg\Requests
 */
class OpenAccountNaturalPersonV25Request extends PacgRequest
{
    protected $key = 'aperturaContoPersonaFisica3In';
    protected $message_name = 'aperturaContoPersonaFisica3';

    /**
     *
     * @var boolean
     */
    protected $encryption_needed = true;

    /**
     *
     * @var boolean
     */
    protected $signature_verification_needed = true;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return OpenAccountNaturalPersonV25Response::class;
    }
}

