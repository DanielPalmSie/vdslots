<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountDocumentRequest;
use IT\Pacg\Requests\QueryAccountDocumentV25Request;
use IT\Pacg\Services\QueryAccountDocumentEntity;

/**
 * Class QueryAccountDocumentAction
 * @package IT\Pacg\Actions
 */
class QueryAccountDocumentAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.5) {
            return QueryAccountDocumentV25Request::class;
        }
        return QueryAccountDocumentRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountDocumentEntity::class;
    }
}

