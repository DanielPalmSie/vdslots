<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\AccountTransactionsReversalRequest;
use IT\Pacg\Services\AccountTransactionsReversalEntity;

/**
 * Class AccountTransactionsReversalAction
 * @package IT\Pacg\Actions
 */
class AccountTransactionsReversalAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AccountTransactionsReversalRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountTransactionsReversalEntity::class;
    }
}