<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\SummaryOfTransactionOperationsRequest;
use IT\Pacg\Services\SummaryOfTransactionOperationsEntity;

/**
 * Class SummaryOfTransactionOperationsAction
 * @package IT\Pacg\Actions
 */
class SummaryOfTransactionOperationsAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return SummaryOfTransactionOperationsRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return SummaryOfTransactionOperationsEntity::class;
    }
}