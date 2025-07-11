<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class ManualAdjustmentFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'manual_adjustment-fraud-flag';
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
        if (
            ($properties['source'] ?? null) !== 'backoffice' ||
            !$this->checkEvent(
                AssignEvent::ON_DEPOSIT_START | AssignEvent::ON_WITHDRAWAL_START | AssignEvent::ON_CASH_TRANSACTION,
                $event
            )
        ) {
            return false;
        }

        $user->setSetting($this->name(), phive()->hisNow(), $this->logDefaultAction);
        $this->logAction($user, $event, $properties);
        return true;
    }
}
