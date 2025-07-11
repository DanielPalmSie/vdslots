<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\TrasversalSelfExclusionManagementAction;
use IT\Pacg\Requests\TrasversalSelfExclusionManagementRequest;
use IT\Pacg\Responses\TrasversalSelfExclusionManagementResponse;
use IT\Pacg\Services\TrasversalSelfExclusionManagementEntity;

/**[
 * Class TrasversalSelfExclusionManagementActionTest
 */
class TrasversalSelfExclusionManagementActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = TrasversalSelfExclusionManagementAction::class;

    /**
     * @var string
     */
    protected $request_name = TrasversalSelfExclusionManagementRequest::class;

    /**
     * @var string
     */
    protected $entity_name = TrasversalSelfExclusionManagementEntity::class;

    /**
     * @var string
     */
    protected $response_name = TrasversalSelfExclusionManagementResponse::class;
}