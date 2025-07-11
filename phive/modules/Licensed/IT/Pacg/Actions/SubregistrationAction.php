<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\SubregistrationRequest;
use IT\Pacg\Services\SubregistrationEntity;

/**
 * Class SubregistrationAction
 * @package IT\Pacg\Actions
 */
class SubregistrationAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return SubregistrationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return SubregistrationEntity::class;
    }
}