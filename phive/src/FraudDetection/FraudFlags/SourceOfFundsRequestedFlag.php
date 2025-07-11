<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class SourceOfFundsRequestedFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'source_of_funds_requested-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        $conditions = (
            RevokeEvent::ON_DOC_DELETION_BO |
            RevokeEvent::ON_DOC_APPROVED_BO |
            RevokeEvent::ON_DOC_REQUESTED_DATA_PROVISION_BO
        );

        if (!$this->checkEvent($conditions, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_DEPOSIT_SUCCESS | AssignEvent::ON_DOC_CREATION_BO, $event)) {
            return false;
        }

        $user->setSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);

        return true;
    }
}
