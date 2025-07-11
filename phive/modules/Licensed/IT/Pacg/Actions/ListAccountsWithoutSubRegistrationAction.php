<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\ListAccountsWithoutSubRegistrationRequest;
use IT\Pacg\Requests\ListAccountsWithoutSubRegistrationV26Request;
use IT\Pacg\Services\ListAccountsWithoutSubRegistrationEntity;

/**
 * Class ListAccountsWithoutSubRegistrationAction
 * @package IT\Pacg\Actions
 */
class ListAccountsWithoutSubRegistrationAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        if ($this->client->getProtocolVersion() >= 2.6) {
            return ListAccountsWithoutSubRegistrationV26Request::class;
        }
        return ListAccountsWithoutSubRegistrationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ListAccountsWithoutSubRegistrationEntity::class;
    }
}
