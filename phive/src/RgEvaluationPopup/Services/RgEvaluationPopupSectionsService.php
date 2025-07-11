<?php

declare(strict_types=1);

namespace Videoslots\RgEvaluationPopup\Services;

use Videoslots\RgEvaluationPopup\Factories\ActionsFactory;
use Videoslots\RgEvaluationPopup\Factories\HeaderFactory;
use Videoslots\RgEvaluationPopup\Factories\DescriptionFactory;

final class RgEvaluationPopupSectionsService
{
    /**
     * @param \DBUser $user
     * @param string  $triggerName
     *
     * @return array
     */
    public function getRgEvaluationPopupSections(\DBUser $user, string $triggerName): array
    {
        $header = (new HeaderFactory())->create();
        $description = (new DescriptionFactory())->create($user, $triggerName);
        $actions = (new ActionsFactory())->create();

        return compact('header', 'description', 'actions');
    }
}
