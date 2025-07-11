<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\ListDormantAccountsRequest;
use IT\Pacg\Requests\ListDormantAccountsV26Request;
use IT\Pacg\Services\ListDormantAccountsEntity;

/**
 * Class ListDormantAccountsAction
 * @package IT\Pacg\Actions
 */
class ListDormantAccountsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.6) {
            return ListDormantAccountsV26Request::class;
        }
        return ListDormantAccountsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ListDormantAccountsEntity::class;
    }
}
