<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Win extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function game()
    {
        return $this->hasOne(Game::class, 'game_id', 'ext_game_name');
    }

}