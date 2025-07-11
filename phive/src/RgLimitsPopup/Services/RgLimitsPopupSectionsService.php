<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Services;

use Videoslots\RgLimitsPopup\Factories\FooterFactory;
use Videoslots\RgLimitsPopup\Factories\HeaderFactory;
use Videoslots\RgLimitsPopup\Factories\RgLimitsContainerFactory;
use Videoslots\RgLimitsPopup\Factories\WinLossContainerFactory;

final class RgLimitsPopupSectionsService
{
    /**
     * @param \DBUser $user
     *
     * @return array
     */
    public function getRgLimitsPopupSections(\DBUser $user): array
    {
        $header = (new HeaderFactory())->create($user, true);
        $winLossContainer = (new WinLossContainerFactory())->create($user);
        $rgLimitsContainer = (new RgLimitsContainerFactory())->create($user);
        $footer = (new FooterFactory())->create($user, true);

        return compact('header', 'winLossContainer', 'rgLimitsContainer', 'footer');
    }
}
