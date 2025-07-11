<?php

declare(strict_types=1);

namespace Videoslots\IntendedGamblingPopup\Services;

use Videoslots\IntendedGamblingPopup\Factories\FooterFactory;
use Videoslots\IntendedGamblingPopup\Factories\HeaderFactory;
use Videoslots\IntendedGamblingPopup\Factories\ContainerFactory;

final class IntendedGamblingPopupService
{
    /**
     * @param \DBUser $user
     *
     * @return array
     */
    public function getIntendedGamblingPopupSections(\DBUser $user): array
    {
        $header = (new HeaderFactory())->create();
        $container = (new ContainerFactory())->create($user);
        $footer = (new FooterFactory())->create();
        return compact('header', 'container', 'footer');
    }
}
