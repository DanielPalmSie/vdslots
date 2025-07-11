<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class TournamentLadder extends FModel
{
    public $timestamps = false;

    protected $table = 'tournament_ladder';
}
