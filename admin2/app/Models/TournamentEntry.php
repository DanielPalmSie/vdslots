<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class TournamentEntry extends FModel
{
    public $timestamps = false;

    protected $table = 'tournament_entries';

    public function tournament()
    {
        return $this->belongsTo(Tournament::class, 't_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
