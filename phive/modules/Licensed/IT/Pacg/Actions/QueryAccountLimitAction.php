<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountLimitRequest;
use IT\Pacg\Services\QueryAccountLimitEntity;

/**
 * Class QueryAccountLimitAction
 * @package IT\Pacg\Actions
 */
class QueryAccountLimitAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QueryAccountLimitRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountLimitEntity::class;
    }
}