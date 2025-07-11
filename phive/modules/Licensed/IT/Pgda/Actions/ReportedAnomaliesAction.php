<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\ReportedAnomaliesRequest;
use IT\Pgda\Services\ReportedAnomaliesEntity;

/**
 * Class ReportedAnomaliesAction
 * @package IT\Pgda\Actions
 */
class ReportedAnomaliesAction extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return ReportedAnomaliesRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ReportedAnomaliesEntity::class;
    }
}