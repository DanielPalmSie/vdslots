<?php

namespace App\Extensions\Database\ZeroDowntimeMigration;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Proxy between silex and laravel formats for service registration
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $pimple
     * @return void
     */
    public function register(Container $pimple)
    {
        (new \Daursu\ZeroDowntimeMigration\ServiceProvider($pimple['capsule.container']))->register();
    }
}