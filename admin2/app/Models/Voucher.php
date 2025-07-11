<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.02.03.
 * Time: 13:55
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Voucher extends FModel
{
    public $timestamps = false;
    protected $table = 'voucher_codes';

    public $guarded = ['id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function bonus_type()
    {
        return $this->hasOne(BonusType::class, 'id', 'bonus_id');
    }

    public function trophy_awards()
    {
        return $this->hasOne(TrophyAwards::class, 'id', 'award_id');
    }
}