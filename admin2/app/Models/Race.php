<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Race extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'races';

    public $timestamps = false;

    public $fillable = [
        'race_type',
        'display_as',
        'levels',
        'prizes',
        'game_categories',
        'games',
        'start_time',
        'end_time',
        'created_at'
    ];

    public function scopeClosed($query)
    {
        return $query->where('closed', 0);
    }
}
