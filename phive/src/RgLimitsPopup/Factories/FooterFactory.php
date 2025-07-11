<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\ButtonData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\FooterData;

final class FooterFactory
{
    /**
     * @param \DBUser $user
     * @param bool $isApi
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\FooterData
     */
    public function create(\DBUser $user, bool $isApi): FooterData
    {
        if ($isApi) {
            $link = \Licensed::RESPONSIBLE_GAMBLINE_ROUTE;
        } else {
            $link = phive('Licensed')->getRespGamingUrl($user, null);
        }

        return new FooterData(
            'rg.info.box.action.info',
            new ButtonData(
                'redirect',
                'rg.info.edit.limits',
                $link
            )
        );
    }
}
