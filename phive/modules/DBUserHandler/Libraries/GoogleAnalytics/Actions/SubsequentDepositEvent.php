<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Actions;

use DBUserHandler\Libraries\GoogleAnalytics\Constants;
use DBUserHandler\Libraries\GoogleAnalytics\Types\GoogleEcommerce;
use DBUser;

class SubsequentDepositEvent extends GoogleEcommerce
{
    /**
     * @var string
     */
    protected string $key = Constants::KEY_SUBSEQUENT_DEPOSIT;

    /**
     * SubsequentDepositEvent constructor.
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
        return 'Subsequent Deposit';
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return 'deposit';
    }
}
