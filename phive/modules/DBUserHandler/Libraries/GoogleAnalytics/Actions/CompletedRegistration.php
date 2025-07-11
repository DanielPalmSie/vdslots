<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Actions;

use DBUserHandler\Libraries\GoogleAnalytics\Constants;
use DBUserHandler\Libraries\GoogleAnalytics\Types\GoogleAnalytics;
use DBUser;

class CompletedRegistration extends GoogleAnalytics
{
    /**
     * @var string
     */
    protected string $key = Constants::KEY_COMPLETED_REGISTRATION;

    /**
     * CompletedRegistration constructor.
     * @param DBUser $user
     */
    public function __construct(DBUser $user)
    {
        parent::__construct($user, $this->key);
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return 'login';
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return 'registration';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Completed Registration';
    }
}
