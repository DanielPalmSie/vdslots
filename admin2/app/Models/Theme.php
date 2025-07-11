<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Theme extends FModel
{
    //protected $table = 'micro_games';

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];
}
