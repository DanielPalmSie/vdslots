<?php

namespace App\Providers;

use App\Listeners\SetCustomerNetDeposit;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Events\ConfigUpdated;
use App\Listeners\AlignCustomerNetDeposit;
use Symfony\Contracts\EventDispatcher\Event;

class EventServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    private const ALIGN_CUSTOMER_NET_DEPOSIT = 'listener.align_customer_net_deposit';
    private const SET_CUSTOMER_NET_DEPOSIT = 'listener.set_customer_net_deposit';

    protected array $listen = [
        ConfigUpdated::NAME => [
            self::SET_CUSTOMER_NET_DEPOSIT,
            self::ALIGN_CUSTOMER_NET_DEPOSIT,
        ],
    ];

    public function register(Container $app): void
    {
        // Register the Listener
        $app[self::SET_CUSTOMER_NET_DEPOSIT] = static function ($app) {
            return new SetCustomerNetDeposit($app['monolog']);
        };
        $app[self::ALIGN_CUSTOMER_NET_DEPOSIT] = static function ($app) {
            return new AlignCustomerNetDeposit($app['monolog']);
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher): void
    {
        foreach ($this->listen as $eventAlias => $listeners) {
            foreach ($listeners as $listenerAlias) {
                $dispatcher->addListener($eventAlias, function (Event $event) use ($app, $listenerAlias) {
                    $app[$listenerAlias]->handle($event);
                });
            }
        }
    }
}