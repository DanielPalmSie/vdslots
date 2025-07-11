<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\TrasversalSelfExclusionManagementRequest;
use IT\Pacg\Services\TrasversalSelfExclusionManagementEntity;

/**
 * Class TrasversalSelfExclusionManagementAction
 * @package IT\Pacg\Actions
 */
class TrasversalSelfExclusionManagementAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return TrasversalSelfExclusionManagementRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return TrasversalSelfExclusionManagementEntity::class;
    }
}