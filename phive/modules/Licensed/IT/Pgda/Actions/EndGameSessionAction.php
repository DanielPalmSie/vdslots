<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\EndGameSessionRequest;
use IT\Pgda\Services\EndGameSessionEntity;

/**
 * Class SessionEndMessageAction
 * @package IT\Pgda\Actions
 */
class EndGameSessionAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return EndGameSessionRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return EndGameSessionEntity::class;
    }
}