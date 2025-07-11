<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\EndParticipationFinalPlayerBalanceRequest;
use IT\Pgda\Services\EndParticipationFinalPlayerBalanceEntity;

/**
 * Class EndParticipationFinalPlayerBalanceAction
 * @package IT\Pgda\Actions
 */
class EndParticipationFinalPlayerBalanceAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return EndParticipationFinalPlayerBalanceRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return EndParticipationFinalPlayerBalanceEntity::class;
    }
}