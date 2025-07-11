<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.02.23.
 * Time: 12:16
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class TrophyAwards extends FModel
{
    const TICKET_TYPES = ['mp-ticket'];

    public $timestamps = false;
    protected $table = 'trophy_awards';
    protected $guarded = ['id', 'created_at'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'action',
        'alias',
        'amount',
        'bonus_code',
        'bonus_id',
        'description',
        'mobile_show',
        'multiplicator',
        'own_valid_days',
        'type',
        'valid_days',
        'jackpots_id',
        'excluded_countries',
    ];

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

    public function isTournamentTicket() {
        return in_array($this->type, self::TICKET_TYPES);
    }

    public function getExcludedCountriesAttribute($value)
    {
        return explode(" ", $value);
    }


}
