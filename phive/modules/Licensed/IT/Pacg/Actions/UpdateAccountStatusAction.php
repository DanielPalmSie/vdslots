<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdateAccountStatusRequest;
use IT\Pacg\Services\UpdateAccountStatusEntity;

/**
 * Class UpdateAccountStatusAction
 * @package IT\Pacg\Actions
 */
class UpdateAccountStatusAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return UpdateAccountStatusRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UpdateAccountStatusEntity::class;
    }
}