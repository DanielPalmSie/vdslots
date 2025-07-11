<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\ListDormantAccountsAction;
use IT\Pacg\Requests\ListDormantAccountsRequest;
use IT\Pacg\Responses\ListDormantAccountsResponse;
use IT\Pacg\Services\ListDormantAccountsEntity;

/**
 * Class ListDormantAccountsActionTest
 */
class ListDormantAccountsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = ListDormantAccountsAction::class;

    /**
     * @var string
     */
    protected $request_name = ListDormantAccountsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = ListDormantAccountsEntity::class;

    /**
     * @var string
     */
    protected $response_name = ListDormantAccountsResponse::class;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getStub();
    }
}