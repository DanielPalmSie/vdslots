<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Services;

final class WinLossWagerDataService
{
    /**
     * @param \DBUser $user
     *
     * @return array
     */
    public function getTotalWinsLossesWagersData(\DBUser $user, $setting = 'rg_info'): array
    {
        $rgInfoSetting = licSetting($setting, $user);
        $data = lic('getWagersWinsLosses', [$user, $setting], $user);

        $formattedData = $this->formatWinsLossesWagersData($data);

        return empty($rgInfoSetting['popup_rg_activity']) ?
            [] : $formattedData;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function formatWinsLossesWagersData(array $data): array
    {
        return [
            'wins' => $data['wins'],
            'wagers' => $data['wagers'],
            'losses' => $data['losses'],
        ];
    }
}
