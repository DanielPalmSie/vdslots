<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdatingOwnerIdDocumentDetailsRequest;
use IT\Pacg\Requests\UpdatingOwnerIdDocumentDetailsV25Request;
use IT\Pacg\Services\UpdatingOwnerIdDocumentDetailsEntity;
use IT\Pacg\Services\UpdatingOwnerIdDocumentDetailsV25Entity;

/**
 * Class UpdatingOwnerIdDocumentDetailsAction
 * @package IT\Pacg\Actions
 */
class UpdatingOwnerIdDocumentDetailsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.5) {
            return UpdatingOwnerIdDocumentDetailsV25Request::class;
        }
        return UpdatingOwnerIdDocumentDetailsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        if ($this->client->getProtocolVersion() >= 2.5) {
            return UpdatingOwnerIdDocumentDetailsV25Entity::class;
        }
        return UpdatingOwnerIdDocumentDetailsEntity::class;
    }
}