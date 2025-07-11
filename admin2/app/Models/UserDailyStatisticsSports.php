<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class UserDailyStatisticsSports extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_daily_stats_sports';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency()
    {
        return $this->hasOne('App\Models\Currency', 'code', 'currency');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }


}
