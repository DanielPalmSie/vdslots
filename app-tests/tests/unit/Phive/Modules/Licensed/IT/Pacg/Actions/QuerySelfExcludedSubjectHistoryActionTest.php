<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\QuerySelfExcludedSubjectHistoryAction;
use IT\Pacg\Requests\QuerySelfExcludedSubjectHistoryRequest;
use IT\Pacg\Responses\QuerySelfExcludedSubjectHistoryResponse;
use IT\Pacg\Services\QuerySelfExcludedSubjectHistoryEntity;

/**
 * Class QuerySelfExcludedSubjectHistoryActionTest
 */
class QuerySelfExcludedSubjectHistoryActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = QuerySelfExcludedSubjectHistoryAction::class;

    /**
     * @var string
     */
    protected $request_name = QuerySelfExcludedSubjectHistoryRequest::class;

    /**
     * @var string
     */
    protected $entity_name = QuerySelfExcludedSubjectHistoryEntity::class;

    /**
     * @var string
     */
    protected $response_name = QuerySelfExcludedSubjectHistoryResponse::class;
}