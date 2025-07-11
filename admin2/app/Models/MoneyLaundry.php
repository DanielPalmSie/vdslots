<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 29/01/2016
 * Time: 16:31
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class MoneyLaundry extends FModel
{
    protected $table = 'money_laundry';

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id', 'user_id');
    }

    public function withdrawal1()
    {
        return $this->belongsTo('App\Models\Withdrawal', 'id', 'w_id1');
    }

    public function withdrawal2()
    {
        return $this->belongsTo('App\Models\Withdrawal', 'id', 'w_id2');
    }

}