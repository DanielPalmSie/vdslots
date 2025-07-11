<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\SummaryOfServiceOperationsRequest;
use IT\Pacg\Services\SummaryOfServiceOperationsEntity;

/**
 * Class SummaryOfServiceOperationsAction
 * @package IT\Pacg\Actions
 */
class SummaryOfServiceOperationsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return SummaryOfServiceOperationsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return SummaryOfServiceOperationsEntity::class;
    }
}