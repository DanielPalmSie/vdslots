<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.02.23.
 * Time: 12:16
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class TrophyAwardOwnership extends FModel
{
    protected $table = 'trophy_award_ownership';

    public function trophy_award()
    {
        return $this->belongsTo(TrophyAwards::class, 'award_id');
    }

    public function users_comments()
    {
        return $this->hasOne(UserComment::class, 'foreign_id', 'id')->where('users_comments.tag', 'trophy_awards_ownership');
    }
}
