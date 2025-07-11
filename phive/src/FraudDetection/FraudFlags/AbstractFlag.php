<?php

declare(strict_types=1);

namespace Videoslots\FraudDetection\FraudFlags;

use CasinoCashier;
use DBUser;
use Psr\Log\LoggerInterface;
use UserHandler;
use Videoslots\FraudDetection\AssignEvent;

abstract class AbstractFlag implements FraudFlagInterface
{
    protected \Config $config;
    protected \SQL $sql;
    protected LoggerInterface $logger;
    protected UserHandler $userHandler;
    protected CasinoCashier $casinoCashier;

    /** @var string|int|null */
    protected $transactionId;

    protected bool $logDefaultAction = false;
    protected string $actionTag = 'withdrawal_fraud_flag';

    /**
     * @param string|int|null $transactionId
     */
    public function __construct(
        \Config         $config,
        \SQL            $sql,
        UserHandler     $userHandler,
        CasinoCashier   $casinoCashier,
        LoggerInterface $logger,
                        $transactionId = null
    )
    {
        $this->config = $config;
        $this->sql = $sql;
        $this->logger = $logger;
        $this->userHandler = $userHandler;
        $this->casinoCashier = $casinoCashier;
        $this->transactionId = $transactionId;
    }

    /**
     * @param string|int|null $transactionId
     */
    public static function create($transactionId = null): AbstractFlag
    {
        return new static(
            phive('Config'),
            phive('SQL'),
            phive('UserHandler'),
            phive('CasinoCashier'),
            phive('Logger')->channel('payments'),
            $transactionId
        );
    }

    protected function checkEvent(int $conditions, int $event): bool
    {
        return (bool)($conditions & $event);
    }

    protected function checkFeatureFlag(string $featureFlag): bool
    {
        $value = $this->config->getValue(
            'withdrawal-flags',
            'enabled-' . $featureFlag,
            'off',
            ["type" => "choice", "values" => ["on", "off"]]
        );

        $this->logger->debug('fraud feature-flag', [
            'name' => $this->name(),
            'result' => $value,
        ]);

        return $value == 'on';
    }

    protected function logAction(
        DBUser $user,
        int    $event,
        ?array $properties = null
    ): void
    {
        $description = "User was flagged with [{$this->name()}]";

        $originatorMap = array_replace(
            array_fill_keys([
                AssignEvent::ON_WITHDRAWAL_PROCESS,
                AssignEvent::ON_WITHDRAWAL_START,
                AssignEvent::ON_WITHDRAWAL_SUCCESS,
                AssignEvent::ON_WITHDRAWAL_CANCELLED
            ], 'withdrawal'),

            array_fill_keys([
                AssignEvent::ON_DEPOSIT_CANCELLED,
                AssignEvent::ON_DEPOSIT_SUCCESS,
                AssignEvent::ON_DEPOSIT_START
            ], 'deposit'),

            [
                AssignEvent::ON_CASH_TRANSACTION => 'cash_transaction',
                AssignEvent::MANUAL => 'manually_triggered_backoffice',
                AssignEvent::ON_DOC_CREATION_BO => 'create_document_backoffice'
            ]
        );

        if (isset($originatorMap[$event])) {
            $description .= " - Originator was [{$originatorMap[$event]}]";
        }

        if ($this->transactionId) {
            $description .= " with ID: [{$this->transactionId}]";
        } elseif (!empty($properties['supplier']) && !empty($properties['amount'])) {
            $description .= sprintf(
                " with supplier: [%s] for amount: [%s] at ~[%s]",
                $properties['supplier'],
                $properties['amount'],
                phive()->hisNow()
            );
        }

        $this->userHandler->logAction($user, $description, $this->actionTag);
    }

    public function postTransactionCreationHandler(\DBUser $user, int $event, ?array $properties = null): bool
    {
        return false;
    }
}
