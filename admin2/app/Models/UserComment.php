<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class UserComment extends FModel
{
    public const TYPE_COMPLAINT = 'complaint';

    public $timestamps = false;

    protected $table = 'users_comments';

    protected $fillable = [
        'user_id',
        'sticky',
        'comment',
        'tag',
        'secret',
        'foreign_id',
        'foreign_id_name',
    ];

    protected $attributes = [
        'foreign_id_name' => '',
        'foreign_id' => 0,
        'tag' => '',
        'secret' => 0
    ];

    public function scopeBonusEntry($query)
    {
        return $query->where('tag', 'bonus_entries');
    }

    public function scopeComplaint($query)
    {
        return $query->where('tag', 'complaint');
    }

    public function scopeLimits($query)
    {
        return $query->where('tag', 'limits');
    }

    public function scopePhoneContact($query)
    {
        return $query->where('tag', 'phone_contact');
    }

    public function scopeTrophyAwardsOwnership($query)
    {
        return $query->where('tag', 'trophy_awards_ownership');
    }


}
