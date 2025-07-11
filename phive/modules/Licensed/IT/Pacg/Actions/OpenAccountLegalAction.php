<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\OpenAccountLegalRequest;
use IT\Pacg\Requests\OpenAccountLegalV26Request;
use IT\Pacg\Services\OpenAccountLegalEntity;
use IT\Pacg\Services\OpenAccountLegalV26Entity;

/**
 * Class OpenAccountLegalAction
 * @package IT\Pacg\Actions
 */
class OpenAccountLegalAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.6) {
            return OpenAccountLegalV26Request::class;
        }
        return OpenAccountLegalRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        if ($this->client->getProtocolVersion() >= 2.6) {
            return OpenAccountLegalV26Entity::class;
        }

        return OpenAccountLegalEntity::class;
    }
}
