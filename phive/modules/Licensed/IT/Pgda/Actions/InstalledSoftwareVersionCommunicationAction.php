<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\InstalledSoftwareVersionCommunicationRequest;
use IT\Pgda\Services\InstalledSoftwareVersionCommunicationEntity;

/**
 * Class InstalledSoftwareVersionCommunicationAction
 * @package IT\Pgda\Actions
 */
class InstalledSoftwareVersionCommunicationAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return InstalledSoftwareVersionCommunicationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return InstalledSoftwareVersionCommunicationEntity::class;
    }
}