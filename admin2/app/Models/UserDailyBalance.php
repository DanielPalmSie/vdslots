<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.04.29.
 * Time: 10:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserDailyBalance extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_daily_balance_stats';

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
