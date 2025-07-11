<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\AccountTransactionsRequest;
use IT\Pacg\Services\AccountTransactionsEntity;

/**
 * Class AccountTransactionsAction
 * @package IT\Pacg\Actions
 */
class AccountTransactionsAction extends AbstractAction
{
    /**
     * @return string
     */
    public function request(): string
    {
        return AccountTransactionsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountTransactionsEntity::class;
    }
}