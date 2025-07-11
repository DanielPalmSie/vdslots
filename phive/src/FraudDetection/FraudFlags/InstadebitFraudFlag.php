<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class InstadebitFraudFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'instadebit-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::ON_DEPOSIT_START, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_DEPOSIT_START, $event)) {
            return false;
        }

        $user->setSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);

        return true;
    }
}
