<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Interfaces;

interface GoogleEventInterface
{
    public function getName(): string;

    public function getAction(): string;

    public function getCategory(): string;

    public function getEvent(): array;

    public function getAnalyticsObject(): array;
}
