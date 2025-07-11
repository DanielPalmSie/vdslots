<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Carbon\Carbon;
use Valitron\Validator;

class GameOverride extends FModel
{
    protected $table = 'game_country_overrides';

    public $timestamps = false;
    
    protected $fillable = [        
        'game_id',
        'country',
        'ext_game_id',
        'ext_launch_id',
        'payout_extra_percent',
        'payout_percent',
        'device_type',
        'device_type_num'
    ];

    protected $guarded = [];

    public function game()
    {
        return $this->hasOne(Game::class, 'id', 'game_id');
    }

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['game_id'], ['country'], ['ext_game_id'], ['ext_launch_id'], ['payout_percent']],
                'max' => [['payout_percent', 1]],
                'min' => [['payout_percent', 0]],
            ]
        ];
    }

    /**
     * TODO we need to move out from models the generic logic
     * @return bool
     */
    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());
        $licensedCountries = phive('Licensed')->getSetting('licensed_countries', []);
        $extraCountries = phive('MicroGames')->getSetting('game_override_extra_countries', []);
        $allCountries = array_merge($licensedCountries, $extraCountries);

        if (GameOverride::query()->where('id', '!=', $this->id)->where(['country' => $this->country, 'game_id' => $this->game_id])->exists()) {
            $validator->error('country', "A game override is already configured for this country.");
        }

        if (in_array($this->country, $allCountries) || BankCountry::query()->where('iso', $this->country)->exists()) {
            if (GameOverride::query()->where('id', '!=', $this->id)->where(['country' => 'ALL', 'game_id' => $this->game_id])->exists()) {
                $validator->error('country', "Specific country overrides are not supported when a Game is configured on ALL countries.");
            }
        } else {
            if ($this->country !== 'ALL') {
                $validator->error('country', "Only ALL or an ISO2 from a country supported for now.");
            }
            if (GameOverride::query()->where('id', '!=', $this->id)->where('game_id', $this->game_id)->where('country', '!=', 'ALL')->exists()) {
                $validator->error('country', "Game override cannot be configured for ALL countries when it has a specific country already.");
            }
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        } else {
            return true;
        }
    }
}
