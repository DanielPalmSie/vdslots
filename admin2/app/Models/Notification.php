<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 14/03/2016
 * Time: 16:31
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Notification extends FModel
{
    protected $table = 'users_notifications';

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id', 'user_id');
    }

}