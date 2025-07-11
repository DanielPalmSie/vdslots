<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 14/12/17
 * Time: 12:16
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class SegmentGroup extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'segments_groups';

    protected $guarded = ['id'];

    public function segment() {
        return $this->hasOne(Segment::class, 'id', 'segment_id');
    }

}