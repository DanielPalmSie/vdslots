<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\AccountDormantRequest;
use IT\Pacg\Services\AccountDormantEntity;

/**
 * Class AccountDormantAction
 * @package IT\Pacg\Actions
 */
class AccountDormantAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AccountDormantRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountDormantEntity::class;
    }
}