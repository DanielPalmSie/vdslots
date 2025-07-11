<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\RgLimitsContainerData;

final class RgLimitsContainerFactory
{
    /**
     * @param \DBUser $user
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\RgLimitsContainerData
     */
    public function create(\DBUser $user): RgLimitsContainerData
    {
        $data = (new RgLimitsDataServiceFactory())->create()->getRgLimitsData($user);
        $rgLimitsMappedData = (new RgLimitsMapperFactory())->create($user)->mapDataInDto($data);

        return new RgLimitsContainerData(
            'rg.info.your.limits',
            $rgLimitsMappedData
        );
    }
}
