<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Factory;

use Videoslots\User\LoginFields\DataTransferObject\MaintenanceData;

final class MaintenanceFactory
{
    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\MaintenanceData
     */
    public function create(): MaintenanceData
    {
        $maintenance = lic('getLicSetting', ['scheduled_maintenance']);

        if (is_array($maintenance) && isset($maintenance['enabled']) && $maintenance['enabled'] === true) {
            return new MaintenanceData(
                "blocked.maintenance.login.html",
                true,
                $maintenance['from'],
                $maintenance['to']
            );
        }

        return $this->createEmpty();
    }

    /**
     * @return \Videoslots\User\LoginFields\DataTransferObject\MaintenanceData
     */
    private function createEmpty(): MaintenanceData
    {
        return new MaintenanceData("", false, "", "");
    }
}
