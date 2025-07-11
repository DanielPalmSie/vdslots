<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\QueryAccountPseudonymAction;
use IT\Pacg\Requests\QueryAccountPseudonymRequest;
use IT\Pacg\Responses\QueryAccountPseudonymResponse;
use IT\Pacg\Services\QueryAccountPseudonymEntity;

/**
 * Class QueryAccountPseudonymActionTest
 */
class QueryAccountPseudonymActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = QueryAccountPseudonymAction::class;

    /**
     * @var string
     */
    protected $request_name = QueryAccountPseudonymRequest::class;

    /**
     * @var string
     */
    protected $entity_name = QueryAccountPseudonymEntity::class;

    /**
     * @var string
     */
    protected $response_name = QueryAccountPseudonymResponse::class;
}