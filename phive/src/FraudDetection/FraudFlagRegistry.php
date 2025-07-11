<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection;

use CasinoCashier;
use Psr\Log\LoggerInterface;
use Videoslots\FraudDetection\FraudFlags\AmlGrsHighRiskProfileFlag;
use Videoslots\FraudDetection\FraudFlags\WelcomeBonusAbuseFlag;
use Videoslots\FraudDetection\FraudFlags\FraudFlagInterface;
use Videoslots\FraudDetection\FraudFlags\IbanCountryMismatchFlag;
use Videoslots\FraudDetection\FraudFlags\IpCountryMismatchFlag;
use Videoslots\FraudDetection\FraudFlags\ManualAdjustmentFlag;
use Videoslots\FraudDetection\FraudFlags\NegativeBalanceSinceDepositFlag;
use Videoslots\FraudDetection\FraudFlags\NeosurfDepositorsFlag;
use Videoslots\FraudDetection\FraudFlags\TooManyRollbacksFlag;
use Videoslots\FraudDetection\FraudFlags\TotalWithdrawalAmountLimitReachedFlag;

class FraudFlagRegistry
{
    private LoggerInterface $logger;
    private array $fraudFlags;

    /**
     * @param string|int|null $transactionId
     */
    public function __construct($transactionId = null)
    {
        /** @var \Config $config */
        $config  = phive('Config');
        /** @var \SQL $sql */
        $sql = phive('SQL');
        /** @var \UserHandler $userHandler */
        $userHandler = phive('UserHandler');
        /** @var CasinoCashier $casinoCashier */
        $casinoCashier = phive('CasinoCashier');

        $this->logger = phive('Logger')->channel('payments');
        $this->fraudFlags = [
            new NeosurfDepositorsFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new AmlGrsHighRiskProfileFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new TooManyRollbacksFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new ManualAdjustmentFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new IpCountryMismatchFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new IbanCountryMismatchFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new TotalWithdrawalAmountLimitReachedFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new NegativeBalanceSinceDepositFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
            new WelcomeBonusAbuseFlag($config, $sql, $userHandler, $casinoCashier, $this->logger, $transactionId),
        ];
    }

    public function assign(\DBUser $user, int $event, ?array $properties = null): void
    {
        foreach ($this->fraudFlags as $flag) {
            $applied = $flag->assign($user, $event, $properties);

            $this->logger->debug('fraud-flag applied', [
                'name' => $flag->name(),
                'event' => $event,
                'user_id' => $user->getId(),
                'properties' => $properties,
                'applied' => $applied
            ]);

        }
    }

    public function revoke(\DBUser $user, int $event): void
    {
        foreach ($this->fraudFlags as $flag) {
            $revoked = $flag->revoke($user, $event);

            $this->logger->debug('fraud-flag revoked', [
                'name' => $flag->name(),
                'event' => $event,
                'user_id' => $user->getId(),
                'revoked' => $revoked,
            ]);
        }
    }

    public function postTransactionCreationHandler(\DBUser $user, int $event, ?array $properties = null): void
    {
        foreach ($this->fraudFlags as $flag) {
            $processed = $flag->postTransactionCreationHandler($user, $event, $properties);

            $this->logger->debug('postTransactionCreationHandler called for fraud-flag', [
                'name' => $flag->name(),
                'event' => $event,
                'user_id' => $user->getId(),
                'properties' => $properties,
                'processed' => $processed
            ]);
        }
    }

    public function names(): array
    {
        return array_map(function (FraudFlagInterface $flag) {
            return $flag->name();
        }, $this->fraudFlags);
    }
}
