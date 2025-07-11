<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class JackpotWheelSlice extends FModel
{
    public $fillable = [
        "wheel_id",
        "award_id",
        "probability",
        "sort_order",
    ];

    /*
    public function award()
    {
        return $this->hasOne(TrophyAwards::class, 'id', 'award_id');
    }
    */

    public function awards(){
        return TrophyAwards::whereIn('id', explode(',', $this->award_id))->get();
    }

    public function wheel()
    {
        return $this->belongsTo('App\Models\JackpotWheel');
    }

}

