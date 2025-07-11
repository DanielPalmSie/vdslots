<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Actions;

use DBUserHandler\Libraries\GoogleAnalytics\Types\GoogleAnalytics;
use DBUserHandler\Libraries\GoogleAnalytics\Constants;
use DBUser;

class PartialRegistration extends GoogleAnalytics
{
    /**
     * @var string
     */
    protected string $key = Constants::KEY_PARTIALLY_REGISTERED;

    /**
     * PartialRegistration constructor.
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
        return 'partially-registered';
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
        return 'Partial Registration';
    }
}
