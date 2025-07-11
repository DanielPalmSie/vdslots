<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\OpenAccountLegalAction;
use IT\Pacg\Requests\OpenAccountLegalRequest;
use IT\Pacg\Responses\OpenAccountLegalResponse;
use IT\Pacg\Services\OpenAccountLegalEntity;

/**
 * Class OpenAccountLegalActionTest
 */
class OpenAccountLegalActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = OpenAccountLegalAction::class;

    /**
     * @var string
     */
    protected $request_name = OpenAccountLegalRequest::class;

    /**
     * @var string
     */
    protected $entity_name = OpenAccountLegalEntity::class;

    /**
     * @var string
     */
    protected $response_name = OpenAccountLegalResponse::class;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getStub();
    }
}