<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdateAccountLimitRequest;
use IT\Pacg\Services\UpdateAccountLimitEntity;

/**
 * Class UpdateAccountLimitAction
 * @package IT\Pacg\Actions
 */
class UpdateAccountLimitAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return UpdateAccountLimitRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UpdateAccountLimitEntity::class;
    }
}