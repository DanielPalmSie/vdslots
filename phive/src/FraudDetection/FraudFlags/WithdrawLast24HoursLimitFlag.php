<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class WithdrawLast24HoursLimitFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'withdraw_last_24_hours_limit-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::ON_WITHDRAWAL_PROCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_PROCESS, $event)) {
            return false;
        }

        $user->refreshSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);
        return true;
    }
}
