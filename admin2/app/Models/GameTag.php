<?php

namespace App\Models;

use App\Extensions\Database\FModel;

/**
 * Class GameTag
 *
 * @property int $id
 * @property string $alias
 * @property array $excluded_countries
 * @property int $filterable
 * @property string|array $comments
 */
class GameTag extends FModel
{
    protected $table = 'game_tags';

    public $timestamps = false;

    protected $fillable = ['alias', 'excluded_countries', 'filterable'];

    protected $guarded = [];
}
