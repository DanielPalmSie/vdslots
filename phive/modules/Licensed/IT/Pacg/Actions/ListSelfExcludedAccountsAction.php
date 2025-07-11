<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\ListSelfExcludedAccountsRequest;
use IT\Pacg\Requests\ListSelfExcludedAccountsV24Request;
use IT\Pacg\Services\ListSelfExcludedAccountsEntity;

/**
 * Class ListSelfExcludedAccountsAction
 * @package IT\Pacg\Actions
 */
class ListSelfExcludedAccountsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.4) {
            return ListSelfExcludedAccountsV24Request::class;
        }
        return ListSelfExcludedAccountsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ListSelfExcludedAccountsEntity::class;
    }
}