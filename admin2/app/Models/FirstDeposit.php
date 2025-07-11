<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class FirstDeposit extends FModel
{
    protected $table = 'first_deposits';

    public $timestamps = false;

}