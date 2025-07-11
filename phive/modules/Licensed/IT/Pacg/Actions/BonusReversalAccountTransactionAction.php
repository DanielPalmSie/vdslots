<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\BonusReversalAccountTransactionsRequest;
use IT\Pacg\Services\BonusReversalAccountTransactionsEntity;

class BonusReversalAccountTransactionAction extends AbstractAction
{
    /**
     * @return string
     */
    public function request(): string
    {
        return BonusReversalAccountTransactionsRequest::class;;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return BonusReversalAccountTransactionsEntity::class;
    }
}