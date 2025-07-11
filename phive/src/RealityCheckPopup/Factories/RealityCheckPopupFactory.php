<?php

declare(strict_types=1);

namespace Videoslots\RealityCheckPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RealityCheckPopup\RealityCheckPopupData;

final class RealityCheckPopupFactory
{
    /**
     * @param array $data
     * @return \Laraphive\Domain\User\DataTransferObjects\RealityCheckPopup\RealityCheckPopupData
     */
    static public function create(array $data): RealityCheckPopupData
    {
        return new RealityCheckPopupData(
            $data['header'],
            $data['title'] ?? '',
            $data['messageString'],
            $data['messageData'],
            $data['buttons'],
            $data['closeButton'] ?? true
        );
    }
}
