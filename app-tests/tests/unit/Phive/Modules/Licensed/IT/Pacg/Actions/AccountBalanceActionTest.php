<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountBalanceAction;
use IT\Pacg\Requests\AccountBalanceRequest;
use IT\Pacg\Responses\AccountBalanceResponse;
use IT\Pacg\Services\AccountBalanceEntity;

/**
 * Class AccountBalanceActionTest
 */
class AccountBalanceActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountBalanceAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountBalanceRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountBalanceEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountBalanceResponse::class;

}