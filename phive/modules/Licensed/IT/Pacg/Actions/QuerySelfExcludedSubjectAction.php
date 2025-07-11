<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QuerySelfExcludedSubjectRequest;
use IT\Pacg\Services\QuerySelfExcludedSubjectEntity;

/**
 * Class QuerySelfExcludedSubjectAction
 * @package IT\Pacg\Actions
 */
class QuerySelfExcludedSubjectAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QuerySelfExcludedSubjectRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QuerySelfExcludedSubjectEntity::class;
    }
}