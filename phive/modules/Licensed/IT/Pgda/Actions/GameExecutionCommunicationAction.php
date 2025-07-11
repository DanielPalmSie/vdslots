<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\GameExecutionCommunicationRequest;
use IT\Pgda\Services\GameExecutionCommunicationEntity;

/**
 * Class GameExecutionCommunicationAction
 * @package IT\Pgda\Actions
 */
class GameExecutionCommunicationAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return GameExecutionCommunicationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return GameExecutionCommunicationEntity::class;
    }
}