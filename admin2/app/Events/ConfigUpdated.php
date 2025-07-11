<?php

namespace App\Events;

use App\Models\Config;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigUpdated extends Event
{
    public const NAME = 'config.updated';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}