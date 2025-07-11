<?php

declare(strict_types=1);

namespace Videoslots\RgEvaluationPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ActionsData;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ButtonData;

final class ActionsFactory
{
    /**
     * @return \Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\ActionsData
     */
    public function create(): ActionsData
    {
        return new ActionsData(
            new ButtonData('continue', 'rg.info.popup.continue'),
            new ButtonData(
                'take-a-break',
                'rg.info.popup.take.break',
                null,
                "logout"
            ),
            new ButtonData(
                'edit-limits',
                'rg.info.popup.edit.limit',
                "responsible-gambling"
            ),
        );
    }
}
