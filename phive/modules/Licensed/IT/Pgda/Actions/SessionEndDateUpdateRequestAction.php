<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\SessionEndDateUpdateRequestRequest;
use IT\Pgda\Services\SessionEndDateUpdateRequestEntity;

/**
 * Class SessionEndDateUpdateMessageAction
 * @package IT\Pgda\Actions
 */
class SessionEndDateUpdateRequestAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return SessionEndDateUpdateRequestRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return SessionEndDateUpdateRequestEntity::class;
    }
}