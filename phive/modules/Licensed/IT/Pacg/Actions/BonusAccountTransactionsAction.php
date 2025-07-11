<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\BonusAccountTransactionsRequest;
use IT\Pacg\Services\BonusAccountTransactionsEntity;

/**
 * Class BonusAccountTransactionsAction
 * @package IT\Pacg\Actions
 */
class BonusAccountTransactionsAction extends AbstractAction
{
    /**
     * @return string
     */
    public function request(): string
    {
        return BonusAccountTransactionsRequest::class;;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return BonusAccountTransactionsEntity::class;
    }
}