<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.06.27.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class WinMp extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $timestamps = false;
    
    protected $table = 'wins_mp';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->hasOne(Game::class, 'game_id', 'ext_game_name');
    }

}