<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class RaceTemplate extends FModel
{
    public $timestamps = false;
    protected $table = 'race_templates';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'race_type',
        'display_as',
        'levels',
        'prizes',
        'prize_type',
        'game_categories',
        'games',
        'recur_type',
        'start_time',
        'start_date',
        'recurring_days',
        'recurring_end_date',
        'duration_minutes'
    ];

    /*
    protected function rules()
    {
        return [
            'default' => [
                'required' => [['alias']],
                'lengthMin' => [['alias', 3]],
            ]
        ];
    }

    public function trophy_award_ownership()
    {
        return $this->hasMany(TrophyAwardOwnership::class, 'award_id', 'id');
    }


//     public function slice()
//     {
//         return $this->hasMany(FortuneSlice::class, 'award_id', 'id');
//     }


    public function bonus()
    {
        return $this->belongsTo(BonusType::class, 'bonus_id');
    }

    public static function getImage($award, $user)
    {
        return phive('Trophy')->getAwardUri($award, $user);
    }
    */

}
