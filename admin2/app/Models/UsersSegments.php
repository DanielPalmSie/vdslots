<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UsersSegments extends FModel
{
    public $timestamps = false;
    protected $table = 'users_segments';

    protected $guarded = [];

    public static function groupInProgress() {
        return (new self())->where('ended_at', '0000-00-00 00:00:00');
    }

    public static function groupEnded() {
        return (new self())->where('ended_at','!=', '0000-00-00 00:00:00');
    }


}