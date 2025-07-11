<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection;

class AssignEvent
{
    public const MANUAL = 1;
    public const ON_WITHDRAWAL_START = 2;
    public const DEPOSIT_BLOCK = 4;
    public const ON_WITHDRAWAL_PROCESS = 8;
    public const ON_DEPOSIT_CANCELLED = 16;
    public const ON_DEPOSIT_SUCCESS = 32;
    public const ON_DEPOSIT_START = 64;
    public const ON_WITHDRAWAL_SUCCESS = 128;
    public const ON_WITHDRAWAL_CANCELLED = 256;
    public const ON_DOC_CREATION_BO = 512;
    public const ON_CASH_TRANSACTION = 1024;
}
