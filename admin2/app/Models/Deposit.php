<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Deposit extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $timestamps = false;

    protected $primaryKey = 'id';

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function ip_log()
    {
        return $this->hasOne(IpLog::class, 'tr_id', 'id')->where('tag', 'deposits');
    }


}