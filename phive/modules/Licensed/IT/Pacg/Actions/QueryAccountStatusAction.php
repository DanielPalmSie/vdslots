<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountStatusRequest;
use IT\Pacg\Services\QueryAccountStatusEntity;

/**
 * Class QueryAccountStatusAction
 * @package IT\Pacg\Actions
 */
class QueryAccountStatusAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QueryAccountStatusRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountStatusEntity::class;
    }
}