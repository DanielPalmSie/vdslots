<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

interface FraudFlagInterface
{
    public function name(): string;
    public function assign(\DBUser $user, int $event, ?array $properties = null): bool;
    public function postTransactionCreationHandler(\DBUser $user, int $event, ?array $properties = null): bool;
    public function revoke(\DBUser $user, int $event): bool;
}
