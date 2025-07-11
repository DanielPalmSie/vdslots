<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\UpdateEmailAccountAction;
use IT\Pacg\Requests\UpdateEmailAccountRequest;
use IT\Pacg\Responses\UpdateEmailAccountResponse;
use IT\Pacg\Services\UpdateEmailAccountEntity;

/**
 * Class UpdateEmailAccountActionTest
 */
class UpdateEmailAccountActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = UpdateEmailAccountAction::class;

    /**
     * @var string
     */
    protected $request_name = UpdateEmailAccountRequest::class;

    /**
     * @var string
     */
    protected $entity_name = UpdateEmailAccountEntity::class;

    /**
     * @var string
     */
    protected $response_name = UpdateEmailAccountResponse::class;
}