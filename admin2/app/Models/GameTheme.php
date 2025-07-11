<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class GameTheme extends FModel
{
    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'game_id',
        'theme_id'
    ];
}
