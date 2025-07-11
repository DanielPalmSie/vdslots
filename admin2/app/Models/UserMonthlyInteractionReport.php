<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.21.16.
 * Time: 10:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserMonthlyInteractionReport extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_monthly_interaction_stats';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }


}
