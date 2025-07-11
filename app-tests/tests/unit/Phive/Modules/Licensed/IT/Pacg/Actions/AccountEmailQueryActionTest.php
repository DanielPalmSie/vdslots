<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\QueryAccountEmailAction;
use IT\Pacg\Requests\QueryAccountEmailRequest;
use IT\Pacg\Responses\QueryAccountEmailResponse;
use IT\Pacg\Services\QueryAccountEmailEntity;

/**
 * Class QueryAccountEmailActionTest
 */
class QueryAccountEmailActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = QueryAccountEmailAction::class;

    /**
     * @var string
     */
    protected $request_name = QueryAccountEmailRequest::class;

    /**
     * @var string
     */
    protected $entity_name = QueryAccountEmailEntity::class;

    /**
     * @var string
     */
    protected $response_name = QueryAccountEmailResponse::class;
}