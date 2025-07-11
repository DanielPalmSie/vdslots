<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountTransactionsAction;
use IT\Pacg\Requests\AccountTransactionsRequest;
use IT\Pacg\Responses\AccountTransactionsResponse;
use IT\Pacg\Services\AccountTransactionsEntity;

/**
 * Class AccountTransactionsActionTest
 */
class AccountTransactionsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountTransactionsAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountTransactionsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountTransactionsEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountTransactionsResponse::class;
}