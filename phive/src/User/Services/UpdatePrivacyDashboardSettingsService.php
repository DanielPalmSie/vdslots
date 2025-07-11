<?php

declare(strict_types=1);

namespace Videoslots\User\Services;

class UpdatePrivacyDashboardSettingsService
{

    /**
     * @api
     *
     * @param array $data
     *
     * @return void
     */
    public function updatePrivacyDashboardSettings(array $data): void
    {
        /** @var \PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');
        $ph->saveFormData($data);
    }
}
