<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\AcquisitionParticipationRightMessageRequest;
use IT\Pgda\Services\AcquisitionParticipationRightMessageEntity;

/**
 * Class AcquisitionParticipationRightMessageAction
 * @package IT\Pgda\Actions
 */
class AcquisitionParticipationRightMessageAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AcquisitionParticipationRightMessageRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AcquisitionParticipationRightMessageEntity::class;
    }
}