<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\Licensed\CA\Traits\FraudFlagTrait;

class InstadebitWithdrawalFlag extends AbstractFlag
{
    use FraudFlagTrait;

    public function name(): string
    {
        return 'instadebit-withdrawal-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        $conditions = (
            AssignEvent::ON_WITHDRAWAL_PROCESS |
            AssignEvent::ON_WITHDRAWAL_SUCCESS |
            AssignEvent::ON_WITHDRAWAL_CANCELLED |
            AssignEvent::ON_DEPOSIT_START |
            AssignEvent::ON_DEPOSIT_CANCELLED |
            AssignEvent::ON_DEPOSIT_SUCCESS
        );

        if (!$this->checkEvent($conditions, $event)) {
            return false;
        }

        $shouldFlagged = $this->manipulateFraudFlagCron(
            $user,
            $properties['dep_type'],
            'instadebit',
            'dep_type',
            $properties['dep_type_scheme']
        );

        if ($shouldFlagged) {
            $user->setSetting($this->name(), 1, $this->logDefaultAction);
            $this->logAction($user, $event, $properties);
            return true;
        }

        return $this->revoke($user, $event);
    }
}
