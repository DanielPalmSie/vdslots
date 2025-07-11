<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\UpdateAccountStatusAction;
use IT\Pacg\Requests\UpdateAccountStatusRequest;
use IT\Pacg\Responses\UpdateAccountStatusResponse;
use IT\Pacg\Services\UpdateAccountStatusEntity;

/**
 * Class UpdateAccountStatusActionTest
 */
class UpdateAccountStatusActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = UpdateAccountStatusAction::class;

    /**
     * @var string
     */
    protected $request_name = UpdateAccountStatusRequest::class;

    /**
     * @var string
     */
    protected $entity_name = UpdateAccountStatusEntity::class;

    /**
     * @var string
     */
    protected $response_name = UpdateAccountStatusResponse::class;
}