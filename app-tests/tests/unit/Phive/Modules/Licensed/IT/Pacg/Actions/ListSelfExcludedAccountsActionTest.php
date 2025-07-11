<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\ListSelfExcludedAccountsAction;
use IT\Pacg\Requests\ListSelfExcludedAccountsRequest;
use IT\Pacg\Responses\ListSelfExcludedAccountsResponse;
use IT\Pacg\Services\ListSelfExcludedAccountsEntity;

/**
 * Class ListSelfExcludedAccountsActionTest
 */
class ListSelfExcludedAccountsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = ListSelfExcludedAccountsAction::class;

    /**
     * @var string
     */
    protected $request_name = ListSelfExcludedAccountsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = ListSelfExcludedAccountsEntity::class;

    /**
     * @var string
     */
    protected $response_name = ListSelfExcludedAccountsResponse::class;
}