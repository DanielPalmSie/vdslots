<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.03.17.
 * Time: 12:16
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserBlocked extends FModel
{
    public $timestamps = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_blocked';

    protected $fillable = [
        'date',
        'username',
        'reason',
        'ip',
        'user_id',
        'actor_id'
    ];

}
