<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Actions;

use DBUserHandler\Libraries\GoogleAnalytics\Types\GoogleEcommerce;
use DBUserHandler\Libraries\GoogleAnalytics\Constants;
use DBUser;

class StartedFirstDepositEvent extends GoogleEcommerce
{
    /**
     * @var string
     */
    protected string $key = Constants::KEY_STARTED_FIRST_DEPOSIT;

    /**
     * StartedFirstDepositEvent constructor.
     * @param DBUser $user
     */
    public function __construct(DBUser $user)
    {
        parent::__construct($user, $this->key);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Started First Deposit';
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return 'deposit';
    }
}

