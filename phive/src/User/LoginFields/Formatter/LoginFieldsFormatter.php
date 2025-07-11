<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\Formatter;

use Videoslots\User\LoginFields\DataTransferObject\LoginFieldsData;
use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData;
use Videoslots\User\ThirdPartyVerificationFields\Formatter\ThirdPartyVerificationFieldsFormatter;

final class LoginFieldsFormatter
{
    /**
     * @param \Videoslots\User\LoginFields\DataTransferObject\LoginFieldsData $data
     *
     * @return array
     */
    public function format(LoginFieldsData $data): array
    {
        return [
            'box_headline_alias' => $data->getTopPartData()->getBoxHeadlineAlias(),
            'default_login' => (new LoginDefaultFormatter())->format($data->getLoginDefaultData()),
            'maintenance' => (new MaintenanceFormatter())->format($data->getMaintenanceData()),
            'third-party-verification' => $this->formatThirdPartyVerification($data->getThirdPartyVerificationFieldsData()),
        ];
    }

    /**
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\ThirdPartyVerificationFieldsData|null $data
     *
     * @return array
     */
    public function formatThirdPartyVerification(?ThirdPartyVerificationFieldsData $data): array
    {
        if ($data === null) {
            return [];
        }

        $response = (new ThirdPartyVerificationFieldsFormatter())->format($data);

        unset($response['box_headline_alias']);

        return $response;
    }
}
