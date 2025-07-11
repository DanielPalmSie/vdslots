<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection;

class RevokeEvent
{
    public const MANUAL = 1;
    public const ON_WITHDRAWAL_SUCCESS = 2;
    public const ON_WITHDRAWAL_PROCESS = 4;
    public const ON_DOC_DELETION_BO = 8;
    public const ON_DOC_REQUESTED_DATA_PROVISION_BO = 16;
    public const ON_DOC_APPROVED_BO = 32;
    public const ON_DEPOSIT_START = 64;
}
