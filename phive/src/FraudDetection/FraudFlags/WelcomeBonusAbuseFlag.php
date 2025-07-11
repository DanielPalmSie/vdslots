<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class WelcomeBonusAbuseFlag extends AbstractFlag
{
    private const REGISTER_DAYS = 30;
    private const WITHDRAWAL_DELAY_HOURS = 2;

    public function name(): string
    {
        return 'welcome-bonus-abuse-fraud-flag';
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (isPNP()) {
            return false;
        }

        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        if (!$this->checkFeatureFlag($this->name())) {
            return false;
        }

        if ($user->registerSince() > self::REGISTER_DAYS) {
            return false;
        }

        $userId = (int)$user->getId();

        if (!$this->activatedWelcomeBonus($user)) {
            return false;
        }

        $deposits = $this->casinoCashier->getUserDeposits($userId, 'ORDER BY id DESC');

        if (!$this->hasCardDeposits($deposits)) {
            return false;
        }

        if ($this->canWithdrawAfterFirstDeposit($deposits[0]['timestamp'])) {
            return false;
        }

        $user->setSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);
        $user->addComment(
            'Withdrawal flag ' . $this->name() . ' triggered due to bonus abuse',
            0,
            'amlfraud'
        );

        return true;
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::ON_WITHDRAWAL_SUCCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }

    private function activatedWelcomeBonus(\DBUser $user): bool
    {
        $userId = $user->getId();

        $lic_bonus = lic('getFirstDepositBonus', [], $user);
        $bonus_type = empty($lic_bonus) ? phive('CasinoBonuses')->getDeposits($user->getAttr('register_date'), '')[0] : $lic_bonus;

        if (empty($bonus_type['id'])) {
            return false;
        }

        $bonus_id = (int)$bonus_type['id'];

        $query = "
            SELECT id
            FROM bonus_entries
            WHERE user_id = $userId
            AND bonus_id = $bonus_id
            AND status IN ('active', 'failed', 'approved')
            LIMIT 1
        ";
        $result = phive('SQL')->sh($userId, '', 'bonus_entries')->getValue($query);

        return (bool) $result;
    }

    private function hasCardDeposits(array $deposits): bool
    {
        $totalRequiredDeposits = (int)$this->config->getValue(
            'withdrawal-flags',
            'welcome-bonus-abuse-fraud-flag-total-deposits',
            2
        );

        $totalDeposits = count($deposits);

        if (!$totalDeposits || $totalDeposits > $totalRequiredDeposits) {
            return false;
        }

        foreach ($deposits as $deposit) {
            if (!in_array($deposit['dep_type'], array_keys($this->casinoCashier->getSetting('ccard_psps')))) {
                return false;
            }
        }

        return true;
    }

    private function canWithdrawAfterFirstDeposit(string $depositTimestamp): bool
    {
        if (
            phive()->hisNow() >
            phive()->hisMod('+' . self::WITHDRAWAL_DELAY_HOURS . ' hour', $depositTimestamp)
        ) {
            return true;
        }

        return false;
    }
}
