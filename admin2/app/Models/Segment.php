<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 14/12/17
 * Time: 12:15
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Segment extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'segments';

    protected $guarded = ['id'];

    public function groups()
    {
        return $this->hasMany('App\Models\SegmentGroup', 'segment_id', 'id');
    }
}