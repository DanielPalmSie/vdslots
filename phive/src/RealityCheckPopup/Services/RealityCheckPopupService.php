<?php

declare(strict_types=1);

namespace Videoslots\RealityCheckPopup\Services;

use Laraphive\Domain\User\DataTransferObjects\RealityCheckPopup\RealityCheckPopupData;
use Videoslots\RealityCheckPopup\Factories\RealityCheckPopupFactory;

final class RealityCheckPopupService
{
    /**
     * @param string $extGameName
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RealityCheckPopup\RealityCheckPopupData
     */
    public function getRealityCheckPopup(string $extGameName): RealityCheckPopupData
    {
        $user = cu();
        $data = lic('getRealityCheck', [$user, 'en', $extGameName, false], $user);

        return RealityCheckPopupFactory::create($data);
    }
}
