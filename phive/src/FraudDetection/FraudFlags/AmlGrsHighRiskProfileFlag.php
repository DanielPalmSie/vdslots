<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\RevokeEvent;

class AmlGrsHighRiskProfileFlag extends AbstractFlag
{
    const HIGH_RISK_TAG = 'High Risk';

    public function name(): string
    {
        return 'aml-grs-high-risk-profile-fraud-flag';
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): bool
    {
        if (!$this->checkEvent(AssignEvent::ON_WITHDRAWAL_START, $event)) {
            return false;
        }

        if (!$this->checkFeatureFlag($this->name())) {
            return false;
        }

        if (!$this->isHighRisk($user)) {
            return false;
        }

        $user->setSetting($this->name(), 1, $this->logDefaultAction);
        $this->logAction($user, $event, $properties);

        $user->setSetting($this->flagCreatedAtSettingName(), phive()->hisNow());

        return true;
    }

    private function isHighRisk(\DBUser $user): bool
    {
        $score = phive("Cashier/Arf")->getLatestRatingScore($user->getId(), 'AML', 'tag');

        $flagCreatedAt = $user->getSetting($this->flagCreatedAtSettingName());
        $flagCreatedAtExpired = $flagCreatedAt && $this->flagCreatedAtExpired($flagCreatedAt);

        if (
            $score === self::HIGH_RISK_TAG &&
            (!$flagCreatedAt || $flagCreatedAtExpired)
        ) {
            return true;
        }

        if ($flagCreatedAtExpired) {
            $user->deleteSetting($this->flagCreatedAtSettingName());
        }

        return false;
    }

    private function flagCreatedAtExpired(string $flagCreatedAt): bool
    {
        $flagDays = $this->config->getValue(
            'withdrawal-flags',
            'high-risk-profile-fraud-flag-days',
            30
        );

        if (
            phive()->hisNow('', 'Y-m-d') >
            phive()->hisMod("+$flagDays day", $flagCreatedAt, 'Y-m-d')
        ) {
            return true;
        }

        return false;
    }

    private function flagCreatedAtSettingName(): string
    {
        return $this->name() . '_created_at';
    }

    public function revoke(\DBUser $user, int $event): bool
    {
        if (!$this->checkEvent(RevokeEvent::ON_WITHDRAWAL_SUCCESS, $event)) {
            return false;
        }

        return $user->deleteSetting($this->name());
    }
}
