<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Trophy extends FModel
{
    public $timestamps = false;
    protected $table = 'trophies';
    protected $guarded  = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'alias',
        'subtype',
        'type',
        'threshold',
        'time_period',
        'time_span',
        'game_ref',
        'in_row',
        'category',
        'sub_category',
        'hidden',
        'amount',
        'award_id',
        'award_id_alt',
        'trademark',
        'repeatable',
        'valid_from',
        'valid_to',
        'completed_ids',
        'included_countries',
        'excluded_countries'
    ];

    protected function rules()
    {
        return [
            'default' => [
                'regex' => [['alias', '/^[a-zA-Z_0-9\-\.]+$/']],
                'required' => [['alias']],
                'lengthMin' => [['alias', 3]],
            ]
        ];
    }

    /**
     * Convert included_countries space delimited string to array
     * 
     * @param $val
     * @return false|string[]
     */
    public function getIncludedCountriesAttribute($val) {
        return explode(' ', $val) ?? [];
    }

    /**
     * Convert excluded_countries space delimited string to array
     *
     * @param $val
     * @return false|string[]
     */
    public function getExcludedCountriesAttribute($val) {
        return explode(' ', $val) ?? [];
    }
    
    public function game()
    {
        return $this->hasOne('App\Models\Game', 'ext_game_name', 'game_ref');
    }

}
