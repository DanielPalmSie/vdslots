<?php

namespace App\Listeners;

use App\Events\ConfigUpdated;
use Psr\Log\LoggerInterface;

abstract class BaseCustomerNetDeposit
{
    protected LoggerInterface $logger;

    protected const CONFIG_NAME = 'global-customer-net-deposit';
    /**
     * @var mixed
     */
    protected $siteEventPublisher;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(ConfigUpdated $event): void
    {
        $config = $event->getConfig();

        if ($config->config_name !== static::CONFIG_NAME) {
            return;
        }
        $action = $this->getAction();
        phive()->fire(
            'config',
            'UpdateGlobalCustomerNetDepositEvent',
            [$action],
            0,
            function () use ($action) {
                phive('Events/AdminEventHandler')->onConfigUpdateGlobalCustomerNetDepositEvent($action);
            });
    }

    abstract protected function getAction(): string;
}