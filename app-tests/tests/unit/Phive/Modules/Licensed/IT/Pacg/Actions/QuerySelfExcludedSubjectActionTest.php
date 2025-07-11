<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\QuerySelfExcludedSubjectAction;
use IT\Pacg\Requests\QuerySelfExcludedSubjectRequest;
use IT\Pacg\Responses\QuerySelfExcludedSubjectResponse;
use IT\Pacg\Services\QuerySelfExcludedSubjectEntity;

/**
 * Class QuerySelfExcludedSubjectActionTest
 */
class QuerySelfExcludedSubjectActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = QuerySelfExcludedSubjectAction::class;

    /**
     * @var string
     */
    protected $request_name = QuerySelfExcludedSubjectRequest::class;

    /**
     * @var string
     */
    protected $entity_name = QuerySelfExcludedSubjectEntity::class;

    /**
     * @var string
     */
    protected $response_name = QuerySelfExcludedSubjectResponse::class;
}