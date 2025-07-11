<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.11.02.
 * Time: 11:05
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserFlag extends FModel
{
    public $fillable = [
        'user_id',
        'flag'
    ];

    public $timestamps = false;

}