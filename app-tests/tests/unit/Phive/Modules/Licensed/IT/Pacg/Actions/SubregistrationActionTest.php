<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\SubregistrationAction;
use IT\Pacg\Requests\SubregistrationRequest;
use IT\Pacg\Responses\SubregistrationResponse;
use IT\Pacg\Services\SubregistrationEntity;

/**
 * Class SubregistrationActionTest
 */
class SubregistrationActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = SubregistrationAction::class;

    /**
     * @var string
     */
    protected $request_name = SubregistrationRequest::class;

    /**
     * @var string
     */
    protected $entity_name = SubregistrationEntity::class;

    /**
     * @var string
     */
    protected $response_name = SubregistrationResponse::class;
}
