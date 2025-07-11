<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\AccountBalanceRequest;
use IT\Pacg\Services\AccountBalanceEntity;

/**
 * Class AccountBalanceAction
 * @package IT\Pacg\Actions
 */
class AccountBalanceAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AccountBalanceRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountBalanceEntity::class;
    }
}