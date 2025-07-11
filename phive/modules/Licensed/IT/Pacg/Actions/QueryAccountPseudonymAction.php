<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountPseudonymRequest;
use IT\Pacg\Services\QueryAccountPseudonymEntity;

/**
 * Class QueryAccountPseudonymAction
 * @package IT\Pacg\Actions
 */
class QueryAccountPseudonymAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QueryAccountPseudonymRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountPseudonymEntity::class;
    }
}