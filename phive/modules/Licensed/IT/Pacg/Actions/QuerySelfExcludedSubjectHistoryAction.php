<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QuerySelfExcludedSubjectHistoryRequest;
use IT\Pacg\Services\QuerySelfExcludedSubjectHistoryEntity;

/**
 * Class QuerySelfExcludedSubjectHistoryAction
 * @package IT\Pacg\Actions
 */
class QuerySelfExcludedSubjectHistoryAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QuerySelfExcludedSubjectHistoryRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QuerySelfExcludedSubjectHistoryEntity::class;
    }
}