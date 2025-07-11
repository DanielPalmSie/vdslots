<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\DescriptionData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\HeaderData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\LastLoginData;

final class HeaderFactory
{
    /**
     * @param \DBUser $user
     * @param bool $isApi
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\HeaderData
     */
    public function create(\DBUser $user, bool $isApi, string $config_name = 'rg_info'): HeaderData
    {
        $rgInfoSetting = licSetting($config_name, $user);
        $forceAccept = ! empty($rgInfoSetting['popup_rg_activity']);

        $imgTypes = [];

        if ($isApi) {
            $imgTypes = $this->getImageTypes($user);
        }

        return new HeaderData(
            $forceAccept,
            $imgTypes,
            new ButtonData(
                'redirect',
                'rg.info.proceed.to.site.' . licJur($user)
            ),
            'rg.info.box.top.headline',
            new DescriptionData(
                'rg.info.box.top.html',
                new LastLoginData(
                    'rg.info.box.last_login_date',
                    ['date' => $user->getPreviousSession()['created_at']]
                )
            )
        );
    }

    /**
     * @param \DBUser $user
     *
     * @return array
     */
    private function getImageTypes(\DBUser $user): array
    {
        $imgTypes = lic('getBaseGameParams', [$user], $user);
        $images = [];

        foreach ($imgTypes as $key => $imgType) {
            if (isset($imgType['img'])) {
                $images[$key] = $imgType;
            }
        }

        return $images;
    }
}
