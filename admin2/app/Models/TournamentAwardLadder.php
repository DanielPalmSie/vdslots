<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class TournamentAwardLadder extends FModel
{
    public $timestamps = false;

    protected $table = 'tournament_award_ladder';
}
