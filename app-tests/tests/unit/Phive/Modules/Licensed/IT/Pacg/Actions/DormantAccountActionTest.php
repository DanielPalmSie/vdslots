<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountDormantAction;
use IT\Pacg\Requests\AccountDormantRequest;
use IT\Pacg\Responses\AccountDormantResponse;
use IT\Pacg\Services\AccountDormantEntity;

/**
 * Class AccountDormantActionTest
 * */
class AccountDormantActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountDormantAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountDormantRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountDormantEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountDormantResponse::class;
}