<?php

namespace App\Listeners;

class AlignCustomerNetDeposit extends BaseCustomerNetDeposit
{
    private const ACTION = 'align';

    protected function getAction(): string
    {
        return static::ACTION;
    }
}