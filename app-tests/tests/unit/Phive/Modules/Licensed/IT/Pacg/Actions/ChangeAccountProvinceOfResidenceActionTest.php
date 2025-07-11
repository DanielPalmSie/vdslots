<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\UpdateAccountProvinceOfResidenceAction;
use IT\Pacg\Requests\UpdateAccountProvinceOfResidenceRequest;
use IT\Pacg\Responses\UpdateAccountProvinceOfResidenceResponse;
use IT\Pacg\Services\UpdateAccountProvinceOfResidenceEntity;

/**
 * Class UpdateAccountProvinceOfResidenceActionTest
 */
class UpdateAccountProvinceOfResidenceActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = UpdateAccountProvinceOfResidenceAction::class;

    /**
     * @var string
     */
    protected $request_name = UpdateAccountProvinceOfResidenceRequest::class;

    /**
     * @var string
     */
    protected $entity_name = UpdateAccountProvinceOfResidenceEntity::class;

    /**
     * @var string
     */
    protected $response_name = UpdateAccountProvinceOfResidenceResponse::class;
}