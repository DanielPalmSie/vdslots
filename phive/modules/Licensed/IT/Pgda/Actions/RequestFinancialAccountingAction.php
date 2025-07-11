<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\RequestFinancialAccountingRequest;
use IT\Pgda\Services\RequestFinancialAccountingEntity;

/**
 * Class RequestFinancialAccountingAction
 * @package IT\Pgda\Actions
 */
class RequestFinancialAccountingAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return RequestFinancialAccountingRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return RequestFinancialAccountingEntity::class;
    }
}