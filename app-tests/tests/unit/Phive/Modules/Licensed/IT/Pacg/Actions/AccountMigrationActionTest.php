<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountMigrationAction;
use IT\Pacg\Requests\AccountMigrationRequest;
use IT\Pacg\Responses\AccountMigrationResponse;
use IT\Pacg\Services\AccountMigrationEntity;

/**
 * Class AccountMigrationActionTest
 */
class AccountMigrationActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountMigrationAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountMigrationRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountMigrationEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountMigrationResponse::class;

}