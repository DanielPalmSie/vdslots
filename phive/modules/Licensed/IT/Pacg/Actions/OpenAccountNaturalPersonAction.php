<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\OpenAccountNaturalPersonRequest;
use IT\Pacg\Services\OpenAccountNaturalPersonEntity;
use IT\Pacg\Requests\OpenAccountNaturalPersonV25Request;
use IT\Pacg\Services\OpenAccountNaturalPersonV25Entity;

/**
 * Class OpenAccountNaturalPersonAction
 * @package IT\Pacg\Actions
 */
class OpenAccountNaturalPersonAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.5) {
            return OpenAccountNaturalPersonV25Request::class;
        }

        return OpenAccountNaturalPersonRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        if ($this->client->getProtocolVersion() >= 2.5) {
            return OpenAccountNaturalPersonV25Entity::class;
        }
        return OpenAccountNaturalPersonEntity::class;
    }

}
