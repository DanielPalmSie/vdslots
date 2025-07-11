<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;

use IT\Pacg\Requests\AccountBonusTransactionsRequest;
use IT\Pacg\Requests\AccountBonusTransactionsV24Request;
use IT\Pacg\Services\AccountBonusTransactionsEntity;

/**
 * Class AccountBonusTransactionsAction
 * @package IT\Pacg\Actions
 */
class AccountBonusTransactionsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.4) {
            return AccountBonusTransactionsV24Request::class;
        }
        return AccountBonusTransactionsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountBonusTransactionsEntity::class;
    }
}