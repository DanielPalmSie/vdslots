<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountEmailRequest;
use IT\Pacg\Services\QueryAccountEmailEntity;

/**
 * Class QueryAccountEmailAction
 * @package IT\Pacg\Actions
 */
class QueryAccountEmailAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QueryAccountEmailRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountEmailEntity::class;
    }
}