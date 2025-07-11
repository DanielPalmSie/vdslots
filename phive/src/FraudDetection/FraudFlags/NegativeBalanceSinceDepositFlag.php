<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use DBUser;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class NegativeBalanceSinceDepositFlag extends AbstractFlag
{
    private bool $shouldWithdrawBlock = false;

    public function name(): string
    {
        return 'negative-balance-since-deposit-fraud-flag';
    }

    public function revoke(DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::MANUAL | RevokeEvent::ON_WITHDRAWAL_SUCCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::MANUAL | AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        if (!$this->checkFeatureFlag($this->name())) {
            return false;
        }

        if ($this->checkEvent(AssignEvent::MANUAL, $event) || $this->hasNegativeBalanceSinceDeposit($user)) {
            $user->setSetting($this->name(), 1, $this->logDefaultAction);
            $this->logAction($user, $event, $properties);

            return true;
        }

        return false;
    }

    private function hasNegativeBalanceSinceDeposit(DBUser $user): bool
    {
        $userId = (int)$user->getId();

        $latestApprovedDepositDate = $this->sql->sh($userId, '', 'deposits')->getValue("
            SELECT timestamp FROM deposits
            WHERE user_id = $userId
            AND status = 'approved'
            ORDER BY timestamp DESC
            LIMIT 1;
        ");

        $latestApprovedWithdrawalDate = $this->sql->sh($userId, '', 'pending_withdrawals')->getValue("
            SELECT approved_at FROM pending_withdrawals
            WHERE user_id = $userId
            AND status = 'approved'
            ORDER BY approved_at DESC
            LIMIT 1;
        ");

        $latestTransactionDate = max([$latestApprovedDepositDate, $latestApprovedWithdrawalDate]);

        $betsWithNegativeBalance = $this->sql->sh($userId, '', 'bets')->loadArray("
            SELECT id FROM bets
            WHERE user_id = $userId
            AND created_at >= '$latestTransactionDate'
            AND balance < 0;
        ");

        if (count($betsWithNegativeBalance) > 0) {
            $this->shouldWithdrawBlock = true;
            return true;
        }

        return false;
    }

    public function postTransactionCreationHandler(DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        if ($this->shouldWithdrawBlock) {
            $user->withdrawBlock();
            return true;
        }

        return false;
    }
}
