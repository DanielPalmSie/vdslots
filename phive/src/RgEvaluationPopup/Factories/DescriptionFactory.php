<?php

declare(strict_types=1);

namespace Videoslots\RgEvaluationPopup\Factories;

use DBUser;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\DescriptionData;
use Laraphive\Domain\User\DataTransferObjects\RgEvaluationPopup\LogoData;
use RgEvaluation\Factory\DynamicVariablesSupplierResolver;

final class DescriptionFactory
{
    /**
     * @param DBUser $user
     * @param string $triggerName
     *
     * @return DescriptionData
     */
    public function create(DBUser $user, string $triggerName): DescriptionData
    {
        $dynamicVariablesSupplier = new DynamicVariablesSupplierResolver($user);

        return new DescriptionData(
            $triggerName . '.rg.info.description.html',
            new LogoData("/diamondbet/images/" . brandedCss() . "warning.png"),
            $dynamicVariablesSupplier->resolve($triggerName)->getRgPopupVariables()
        );
    }
}
