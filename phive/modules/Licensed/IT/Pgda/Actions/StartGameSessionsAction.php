<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\StartGameSessionsRequest;
use IT\Pgda\Services\StartGameSessionsEntity;

/**
 * Class StartGameSessionsAction
 * @package IT\Pgda\Actions
 */
class StartGameSessionsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return StartGameSessionsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return StartGameSessionsEntity::class;
    }
}