<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Mappers;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossTypesData;

final class TotalsMapper
{
    /**
     * @param array $data
     *
     * @return array<\Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossTypesData>
     */
    public function mapDataInDto(array $data): array
    {
        $totals = [];

        foreach ($data as $type => $item) {
            $totals[] = new WinLossTypesData(
                $type,
                sprintf('rg.info.popup.%s', $type),
                nfCents($item, true),
            );
        }

        return $totals;
    }
}
