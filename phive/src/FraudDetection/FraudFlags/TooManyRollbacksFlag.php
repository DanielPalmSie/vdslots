<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Cashier;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class TooManyRollbacksFlag extends AbstractFlag
{
    const CASH_TRANSACTION_ROLLBACK_WITHDRAWAL = 82;

    public function name(): string
    {
        return 'too_many_rollbacks-fraud-flag';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::MANUAL | RevokeEvent::ON_WITHDRAWAL_SUCCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if ($this->checkEvent(AssignEvent::MANUAL, $event) || $this->shouldAssignFlag($user, $event)) {
            $user->setSetting($this->name(), 1, $this->logDefaultAction);
            $this->logAction($user, $event, $properties);
            return true;
        }

        return false;
    }

    private function shouldAssignFlag(\DBUser $user, ?int $event): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        $rollbacksInfo = $this->getRollbacksInfo((int)$user->getId());
        $actualRollbacksCount = (int)$rollbacksInfo->rollbacks_count;
        $actualRollbacksAmount = (int)$rollbacksInfo->rollbacks_amount;

        if ($actualRollbacksCount === 0) {
            return false;
        }

        $withdrawalFlagsConfigTag = 'withdrawal-flags';
        $configs = $this->config->getByTags('withdrawal-flags', true)[$withdrawalFlagsConfigTag];

        $rollbacksThreshold = $configs['number-of-rollbacks'];
        $rollbacksAmount = $configs['rollbacks-amount-euro-cents'];

        return $actualRollbacksCount > $rollbacksThreshold || $actualRollbacksAmount > mc($rollbacksAmount, $user);
    }

    private function getRollbacksInfo(int $user_id): object
    {
        $lastSuccessfulWD = $this->casinoCashier->getLastPending(null, $user_id);

        // Base query since registration date
        $sqlQuery = "
            SELECT COUNT(*) AS rollbacks_count, COALESCE(ABS(SUM(amount)), 0) AS rollbacks_amount
            FROM cash_transactions
            WHERE user_id = {$user_id}
                AND transactiontype = " . self::CASH_TRANSACTION_ROLLBACK_WITHDRAWAL . "
        ";

        if ($lastSuccessfulWD) {
            $subQuery = "
                AND timestamp > (
                    SELECT MAX(timestamp)
                    FROM cash_transactions
                    WHERE user_id = {$user_id}
                      AND transactiontype = " . Cashier::CASH_TRANSACTION_WITHDRAWAL . "
                      AND parent_id = {$lastSuccessfulWD['id']}
                )
            ";
            $sqlQuery .= $subQuery;
        }

        return $this->sql->readOnly()->sh($user_id, '', 'cash_transactions')->loadObject($sqlQuery);
    }
}
