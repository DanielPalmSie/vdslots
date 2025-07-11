<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Valitron\Validator;

class JackpotWheelLog extends FModel
{
    protected $table = 'jackpot_wheel_log';

    public function wheel()
    {
        return $this->belongsTo('App\Models\JackpotWheel');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Users');
    }

    public function award()
    {
        return $this->belongsTo('App\Models\Trophy');
    }

}

