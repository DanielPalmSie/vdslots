<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\QueryAccountLimitAction;
use IT\Pacg\Requests\QueryAccountLimitRequest;
use IT\Pacg\Responses\QueryAccountLimitResponse;
use IT\Pacg\Services\QueryAccountLimitEntity;

/**
 * Class QueryAccountLimitActionTest
 */
class QueryAccountLimitActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = QueryAccountLimitAction::class;

    /**
     * @var string
     */
    protected $request_name = QueryAccountLimitRequest::class;

    /**
     * @var string
     */
    protected $entity_name = QueryAccountLimitEntity::class;

    /**
     * @var string
     */
    protected $response_name = QueryAccountLimitResponse::class;
}