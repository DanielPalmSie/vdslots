<?php

declare(strict_types=1);

namespace App\Listeners;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

class SessionListener extends AbstractSessionListener
{
    private $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    protected function getSession(): ?SessionInterface
    {
        if (!isset($this->app['session'])) {
            return null;
        }

        return $this->app['session'];
    }
}