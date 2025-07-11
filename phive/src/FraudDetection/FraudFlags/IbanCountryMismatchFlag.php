<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class IbanCountryMismatchFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'iban_country_mismatch-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::ON_WITHDRAWAL_SUCCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_PROCESS, $event)) {
            return false;
        }

        if ($properties['config']['iban-country-mismatch'] == 'yes') {
            if (
                !empty($properties['withdrawal']['iban']) &&
                $user->getCountry() != strtoupper(substr($properties['withdrawal']['iban'], 0, 2))
            ) {
                $user->setSetting($this->name(), 1, $this->logDefaultAction);
                $this->logAction($user, $event, $properties);

                return true;
            }
        }

        return false;
    }
}
