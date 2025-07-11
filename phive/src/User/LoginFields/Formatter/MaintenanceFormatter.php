<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Formatter;

use Videoslots\User\LoginFields\DataTransferObject\MaintenanceData;

final class MaintenanceFormatter
{
    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\MaintenanceData $data
     *
     * @return array
     */
    public function format(MaintenanceData $data): array
    {
        return [
            'enabled' => $data->isEnabled(),
            'message' => [
                'alias' => $data->getAlias(),
                'placeholders' => [
                    'start_time' => $data->getFrom(),
                    'end_time' => $data->getTo(),
                ],
            ],
        ];
    }
}
