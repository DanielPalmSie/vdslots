<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\GameSessionsAlignmentCommunicationRequest;
use IT\Pgda\Services\GameSessionsAlignmentCommunicationEntity;

/**
 * Class GameSessionsAlignmentCommunicationAction
 * @package IT\Pgda\Actions
 */
class GameSessionsAlignmentCommunicationAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return GameSessionsAlignmentCommunicationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return GameSessionsAlignmentCommunicationEntity::class;
    }
}