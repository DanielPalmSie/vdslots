<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\UpdatingOwnerIdDocumentDetailsAction;
use IT\Pacg\Requests\UpdatingOwnerIdDocumentDetailsRequest;
use IT\Pacg\Responses\UpdatingOwnerIdDocumentDetailsResponse;
use IT\Pacg\Services\UpdatingOwnerIdDocumentDetailsEntity;

/**
 * Class UpdatingOwnerIdDocumentDetailsActionTest
 */
class UpdatingOwnerIdDocumentDetailsActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = UpdatingOwnerIdDocumentDetailsAction::class;

    /**
     * @var string
     */
    protected $request_name = UpdatingOwnerIdDocumentDetailsRequest::class;

    /**
     * @var string
     */
    protected $entity_name = UpdatingOwnerIdDocumentDetailsEntity::class;

    /**
     * @var string
     */
    protected $response_name = UpdatingOwnerIdDocumentDetailsResponse::class;
}