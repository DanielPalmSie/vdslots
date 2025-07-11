<?php

declare(strict_types=1);

namespace Videoslots\IntendedGamblingPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ContainerData;

final class ContainerFactory
{
    /**
     * @param \DBUser $user
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ContainerData
     */
    public function create(\DBUser $user): ContainerData
    {
        $lic_ranges = lic('getIntendedGamblingRanges', [$user]) ?? [];
        $default_ranges = [["value" => null, "label" => 'intended_gambling.form.placeholder']];
        $ranges = array_merge($default_ranges, $lic_ranges);
        $currency = lic('getIntendedGamblingCurrency', [$user]);
        return new ContainerData(
            $currency,
            $ranges
        );
    }
}
