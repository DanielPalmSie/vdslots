<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\SummaryOfServiceOperationsAction;
use IT\Pacg\Requests\SummaryOfServiceOperationsRequest;
use IT\Pacg\Responses\SummaryOfServiceOperationsResponse;
use IT\Pacg\Services\SummaryOfServiceOperationsEntity;

/**
 * Class SummaryOfServiceOperationsActionTest
 */
class SummaryOfServiceOperationsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = SummaryOfServiceOperationsAction::class;

    /**
     * @var string
     */
    protected $request_name = SummaryOfServiceOperationsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = SummaryOfServiceOperationsEntity::class;

    /**
     * @var string
     */
    protected $response_name = SummaryOfServiceOperationsResponse::class;
}
