<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\ListAccountsWithoutSubRegistrationAction;
use IT\Pacg\Requests\ListAccountsWithoutSubRegistrationRequest;
use IT\Pacg\Responses\ListAccountsWithoutSubRegistrationResponse;
use IT\Pacg\Services\ListAccountsWithoutSubRegistrationEntity;

/**
 * Class ListAccountsWithoutSubRegistrationActionTest
 */
class ListAccountsWithoutSubRegistrationActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = ListAccountsWithoutSubRegistrationAction::class;

    /**
     * @var string
     */
    protected $request_name = ListAccountsWithoutSubRegistrationRequest::class;

    /**
     * @var string
     */
    protected $entity_name = ListAccountsWithoutSubRegistrationEntity::class;

    /**
     * @var string
     */
    protected $response_name = ListAccountsWithoutSubRegistrationResponse::class;
}