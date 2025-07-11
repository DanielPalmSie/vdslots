<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class RegulatoryStats extends FModel
{
    public $timestamps = false;

    protected $table = 'regulatory_stats';

    const TYPE_UNITS = 'units';
    const TYPE_MONEY = 'money';
    const TYPE_PERCENT = 'percent';
}

