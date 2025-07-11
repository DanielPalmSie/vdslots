<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdateEmailAccountRequest;
use IT\Pacg\Services\UpdateEmailAccountEntity;

/**
 * Class UpdateEmailAccountAction
 * @package IT\Pacg\Actions
 */
class UpdateEmailAccountAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return UpdateEmailAccountRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UpdateEmailAccountEntity::class;
    }
}