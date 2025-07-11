<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\UpdateAccountLimitAction;
use IT\Pacg\Requests\UpdateAccountLimitRequest;
use IT\Pacg\Responses\UpdateAccountLimitResponse;
use IT\Pacg\Services\UpdateAccountLimitEntity;

/**
 * Class UpdateAccountLimitActionTest
 */
class UpdateAccountLimitActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = UpdateAccountLimitAction::class;

    /**
     * @var string
     */
    protected $request_name = UpdateAccountLimitRequest::class;

    /**
     * @var string
     */
    protected $entity_name = UpdateAccountLimitEntity::class;

    /**
     * @var string
     */
    protected $response_name = UpdateAccountLimitResponse::class;
}