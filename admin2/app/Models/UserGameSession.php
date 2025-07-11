<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserGameSession extends FModel
{
    protected $table = 'users_game_sessions';

    public function game()
    {
        return $this->hasOne('App\Models\Game', 'ext_game_name', 'game_ref');
    }

}
