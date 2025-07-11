<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\AccountBonusTransactionsAction;
use IT\Pacg\Requests\AccountBonusTransactionsRequest;
use IT\Pacg\Responses\AccountBonusTransactionsResponse;
use IT\Pacg\Services\AccountBonusTransactionsEntity;

/**
 * Class AccountBonusTransactionsActionTest
 */
class AccountBonusTransactionsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = AccountBonusTransactionsAction::class;

    /**
     * @var string
     */
    protected $request_name = AccountBonusTransactionsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AccountBonusTransactionsEntity::class;

    /**
     * @var string
     */
    protected $response_name = AccountBonusTransactionsResponse::class;
}