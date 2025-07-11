<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class NeosurfDepositorsFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'neosurf-depositors-fraud-flag';
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
        $userId = $user->getId();

        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        if (!$this->checkFeatureFlag($this->name())) {
            return false;
        }

        $depositGroups = $this->sql->sh($userId, '', 'deposits')->loadArray("
            SELECT SUM(amount) as sum, COUNT(id) as count, dep_type FROM deposits
            WHERE user_id = $userId
            AND status = 'approved'
            GROUP BY dep_type
        ;");

        $depositsAmount = array_reduce($depositGroups, function (int $carry, array $item) {
            return $carry + $item['sum'];
        }, 0);

        $currentWithdrawalSum = (int)$this->sql->sh($userId, '', 'pending_withdrawals')->getValue("
            SELECT SUM(amount) FROM pending_withdrawals
            WHERE user_id = $userId
            AND status != 'disapproved'
        ;");

        $threshold = (float)$this->config->getValue(
            'withdrawal-flags',
            'neosurf-withdrawals-over-deposits-percentage',
            0.85
        );

        $depositOtherThanNeosurf = array_reduce($depositGroups, function (int $carry, array $item) {
            return $carry || ($item['dep_type'] != 'neosurf');
        }, false);

        $withdrawalSum = $currentWithdrawalSum + (100 * $properties['amount']);

        $this->logger->debug('fraud-flag debug', [
            'name' => $this->name(),
            'deposits' => $depositGroups,
            'deposit-sum' => $depositsAmount,
            'withdrawal-sum' => $withdrawalSum,
            'threshold' => $threshold,
            'deposits-other-than-neosurf' => $depositOtherThanNeosurf,
        ]);

        if ($depositOtherThanNeosurf || !$depositsAmount) {
            return false;
        }

        if (($withdrawalSum / $depositsAmount) > $threshold) {
            $user->setSetting($this->name(), 1);
            return true;
        }

        return false;
    }
}
