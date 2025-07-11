<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\TotalWinLossData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossContainerData;
use Videoslots\RgLimitsPopup\Mappers\TotalsMapper;

final class WinLossContainerFactory
{
    /**
     * @param \DBUser $user
     * @Param \Array $extra
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossContainerData
     */
    public function create(\DBUser $user, array $extra = []): WinLossContainerData
    {
        if(!empty($extra['setting']) ) {

            $rgTypeInfoSetting = licSetting($extra['setting'], $user);
            $wagerResult = $user->getNetLossBetweenDates(phive()->hisMod($rgTypeInfoSetting['period']), phive()->hisNow());
            $data = (new WinLossWagerDataServiceFactory())->create()->getTotalWinsLossesWagersData($user, $extra['setting']);
        } else {
            $wagerResult = lic('getWagerResult', [$user], $user);
            $data = (new WinLossWagerDataServiceFactory())->create()->getTotalWinsLossesWagersData($user);
        }

        $totalWinsLossesWagersMappedData = (new TotalsMapper())->mapDataInDto($data);

        return new WinLossContainerData(
            'rg.info.popup.winloss',
            ($extra['subheader'] ?? 'rg.info.popup.winloss.period') . ".". licJur($user),
            new ButtonData(
                'show',
                'show.total'
            ),
            $user->getCurrency(),
            nfCents($wagerResult, true),
            new TotalWinLossData(
                'rg.info.popup.activity_period',
                $totalWinsLossesWagersMappedData
            )
        );
    }
}
