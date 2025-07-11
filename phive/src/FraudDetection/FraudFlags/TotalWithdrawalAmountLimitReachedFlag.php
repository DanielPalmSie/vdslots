<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use DBUser;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class TotalWithdrawalAmountLimitReachedFlag extends AbstractFlag
{
    public function name(): string
    {
        return 'total-withdrawal-amount-limit-reached-fraud-flag';
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

        if (
            $this->checkEvent(AssignEvent::MANUAL, $event) ||
            $this->isTotalWithdrawalLimitBreached($user, $properties['amount'])
        ) {
            $user->setSetting($this->name(), 1, $this->logDefaultAction);
            $this->logAction($user, $event, $properties);
            return true;
        }

        return false;
    }

    /**
     * We check for the first X days (configurable) since the first deposit if we have:
     * - an amount of WD (approved + pending + current one) > total amount of deposits
     * - overlapping game sessions
     * if the above are both true we add "total-withdrawal-amount-limit-reached-fraud-flag" to the player.
     */
    private function isTotalWithdrawalLimitBreached(DBUser $user, string $currentWithdrawAmount): bool
    {
        $currentWithdrawAmountInCents = (int)($currentWithdrawAmount * 100);
        $userId = (int)$user->getId();
        $firstDeposit = $this->casinoCashier->getFirstDeposit($userId);
        if (empty($firstDeposit)) {
            return false;
        }

        $firstDepositDate = $firstDeposit['timestamp'];

        $days = (int)$this->config->getValue('withdrawal-flags', 'number-of-days-for-total-withdrawal-amount-limit', 30);
        if (phive()->hisMod("+$days days", $firstDepositDate) <= phive()->hisNow()) {
            return false;
        }

        $sumApprovedDeposits = (int)$this->sql->sh($userId, '', 'deposits')->getValue("
            SELECT SUM(amount) AS amount FROM deposits
            WHERE user_id = $userId
            AND timestamp BETWEEN '$firstDepositDate' AND ('$firstDepositDate' + INTERVAL $days DAY)
            AND status = 'approved'
        ");

        $sumPendingAndAprovedWithdrawals = (int)$this->sql->sh($userId, '', 'pending_withdrawals')->getValue("
            SELECT SUM(amount) AS amount FROM pending_withdrawals
            WHERE user_id = $userId
            AND timestamp BETWEEN '$firstDepositDate' AND ('$firstDepositDate' + INTERVAL $days DAY)
            AND status IN('approved', 'pending')
        ");

        $sumTotalWithdrawals = $sumPendingAndAprovedWithdrawals + $currentWithdrawAmountInCents;
        if ($sumTotalWithdrawals <= $sumApprovedDeposits) {
            return false;
        }

        // Table games logic
        $tableGames = $this->config->getValue('withdrawal-flags', 'table-games');
        $microGamesJoin = $tableGames ? 'JOIN micro_games mg ON mg.ext_game_name = ugs.game_ref' : '';
        $microGamesWhere = $tableGames ? "AND mg.tag IN ('" . implode("','", explode(',', $tableGames)) . "')" : '';
        $gameSessionsGroupBy = $tableGames ? 'GROUP BY ugs.id' : '';

        $gameSessions = $this->sql->sh($userId, '', 'users_game_sessions')->loadArray("
            SELECT ugs.start_time, ugs.end_time
            FROM users_game_sessions ugs
            $microGamesJoin
            WHERE
                ugs.user_id = $userId AND
                ugs.start_time BETWEEN '$firstDepositDate' AND
                ('$firstDepositDate' + INTERVAL $days DAY)
                $microGamesWhere
            $gameSessionsGroupBy
            ORDER BY ugs.start_time ASC
        ");

        $overlappingSession = false;
        foreach ($gameSessions as $i => $gameSession) {
            $currentStartTime = $gameSession['start_time'];
            $currentEndTime = $gameSession['end_time'];
            if ($i === 0) {
                $previousStartTime = $currentStartTime;
                $previousEndTime = $currentEndTime;
                continue;
            }
            if (($currentStartTime >= $previousStartTime && $previousEndTime > $currentStartTime)
                || ($currentEndTime === '0000-00-00 00:00:00' && $previousEndTime === '0000-00-00 00:00:00')) {
                $overlappingSession = true;
                break;
            }
            $previousStartTime = $currentStartTime;
            $previousEndTime = $currentEndTime;
        }

        if ($overlappingSession) {
            return true;
        }

        // Sport bets logic
        $minutesBetweenCreationAndVoid = $this->config->getValue('withdrawal-flags', 'minutes-between-creation-and-void-for-total-withdrawal-amount-limit-reached', 5);
        $thresholdOfQuickCashoutBets = $this->config->getValue('withdrawal-flags', 'threshold-of-quick-cashout-bets-for-total-withdrawal-amount-limit-reached', 3);
        $countOfQuickCashoutBets = $this->sql->sh($userId, '', 'sport_transactions')->getValue("
            SELECT
                count(sport_transactions.id) as quick_cashout_count
            FROM
                sport_transactions
            INNER JOIN
                sport_transaction_info ON sport_transactions.id = sport_transaction_info.sport_transaction_id
                AND sport_transactions.ticket_id = sport_transaction_info.ticket_id
            WHERE
                sport_transactions.network = 'altenar'
                AND sport_transactions.settled_at <= DATE_ADD(sport_transactions.created_at, INTERVAL $minutesBetweenCreationAndVoid MINUTE)
                AND sport_transaction_info.transaction_type = 'CashoutBet'
                AND sport_transactions.created_at > DATE_SUB(now(), INTERVAL $days DAY)
                AND sport_transactions.user_id = $userId
        ");

        if ($countOfQuickCashoutBets >= $thresholdOfQuickCashoutBets) {
            return true;
        }

        return false;
    }
}
