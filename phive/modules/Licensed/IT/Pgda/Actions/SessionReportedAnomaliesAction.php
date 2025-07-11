<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\SessionReportedAnomaliesRequest;
use IT\Pgda\Services\SessionReportedAnomaliesEntity;

/**
 * Class SessionReportedAnomaliesAction
 * @package IT\Pgda\Actions
 */
class SessionReportedAnomaliesAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return SessionReportedAnomaliesRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return SessionReportedAnomaliesEntity::class;
    }
}