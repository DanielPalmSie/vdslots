<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class WithdrawalLimitFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'withdraw_limit-fraud-flag';
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

        $user->setSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);
        return true;
    }
}
