<?php

namespace App\Models;

use App\Extensions\Database\FModel;

/**
 * Class GameTagConnection
 *
 * @property int $id
 * @property int $tag_id
 * @property int $game_id
 */
class GameTagConnection extends FModel
{
    protected $table = 'game_tag_con';

    public $timestamps = false;

    protected $fillable = ['tag_id', 'game_id'];

    protected $guarded = [];

}
