<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountTransactionsReversalAction;
use IT\Pacg\Requests\AccountTransactionsReversalRequest;
use IT\Pacg\Responses\AccountTransactionsReversalResponse;
use IT\Pacg\Services\AccountTransactionsReversalEntity;

/**
 * Class AccountTransactionsReversalActionTest
 */
class AccountTransactionsReversalActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountTransactionsReversalAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountTransactionsReversalRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountTransactionsReversalEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountTransactionsReversalResponse::class;
}