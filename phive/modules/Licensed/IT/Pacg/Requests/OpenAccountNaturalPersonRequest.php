<?php

namespace IT\Pacg\Requests;

use IT\Pacg\Responses\OpenAccountNaturalPersonResponse;

/**
 * Class OpenAccountNaturalPersonRequest
 * @package IT\Pacg\Requests
 */
class OpenAccountNaturalPersonRequest extends PacgRequest
{
    protected $key = 'aperturaContoPersonaFisica2In';
    protected $message_name = 'aperturaContoPersonaFisica2';

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
        return OpenAccountNaturalPersonResponse::class;
    }
}

