<?php

namespace DBUserHandler;

class DBUserRestriction
{
    protected array $restrictionReasonsDescriptionsMap = [
        'cdd_check' => "CDD triggered",
        'kyc_check' => "Failed the ID3 check",
        'sowd' => "User did not complete SOWd for more than 30 days",
        'temporal_account' => "Temporal account is restricted after 30 days",
    ];

    public const CDD_CHECK = "cdd_check";
    public const KYC_CHECK = "kyc_check";
    public const SOWD = "sowd";
    public const TEMPORAL_ACCOUNT = "temporal_account";

    public function getRestrictionDescription(string $restrictionReason): ?string
    {
        return $this->restrictionReasonsDescriptionsMap[$restrictionReason] ?? null;
    }
}
