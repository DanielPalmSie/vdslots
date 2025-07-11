<?php

namespace App\Listeners;

class SetCustomerNetDeposit extends BaseCustomerNetDeposit
{
    private const ACTION = 'set';

    protected function getAction(): string
    {
        return static::ACTION;
    }
}