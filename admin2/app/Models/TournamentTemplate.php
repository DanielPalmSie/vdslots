<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Extensions\Database\FManager as DB;
use Valitron\Validator;

class TournamentTemplate extends FModel
{
    public $timestamps = false;
    protected $table = 'tournament_tpls';
    protected $guarded  = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'game_ref',
        'tournament_name',
        'category',
        'start_format',
        'win_format',
        'play_format',
        'cost',
        'pot_cost',
        'xspin_info',
        'min_players',
        'max_players',
        'mtt_show_hours_before',
        'duration_minutes',
        'mtt_start_time',
        'mtt_start_date',
        'mtt_reg_duration_minutes',
        'mtt_late_reg_duration_minutes',
        'mtt_recur_type',
        'mtt_recur_days',
        'recur_end_date',
        'recur',
        'guaranteed_prize_amount',
        'prize_type',
        'max_bet',
        'min_bet',
        'house_fee',
        'get_race',
        'get_loyalty',
        'get_trophy',
        'rebuy_times',
        'rebuy_cost',
        'turnover_threshold',
        'award_ladder_tag',
        'duration_rebuy_minutes',
        'ladder_tag',
        'included_countries',
        'excluded_countries',
		'reg_wager_lim',
        'reg_dep_lim',
        'reg_lim_period',
        'reg_lim_excluded_countries',
        'free_pot_cost',
        'prize_calc_wait_minutes',
        'allow_bonus',
        'total_cost',
        'rebuy_house_fee',
        'spin_m',
        'pwd',
        'number_of_jokers',
        'bounty_award_id',
        'bet_levels',
        'desktop_or_mobile',
        'queue',
		'blocked_provinces',
	];

    public function user()
    {
        return $this->hasOne('App\Models\Game', 'ext_game_name', 'game_ref');
    }

    public function game()
    {
        return $this->hasOne('App\Models\Game', 'ext_game_name', 'game_ref');
    }

    public static function getColumnsData()
    {
        $instance = new static;
        $column_data = DB::select('SHOW COLUMNS FROM ' . $instance->getTable());
        $adapted_column_data = [];

        foreach ($column_data as $value) {
            $type_simplified = "text";

            $pos_int = stripos($value->Type, 'int');
            if ($pos_int !== false) {
                $type_simplified = 'number';
            }

            $pos_date = stripos($value->Type, 'date');
            if ($pos_date !== false) {
                $type_simplified = 'date';
            }

            $adapted_column_data[$value->Field] = ['type' => $value->Type, 'type_simple' => $type_simplified, 'NULL' => $value->Null == "NO", 'default' => $value->Default];
        }

        return $adapted_column_data;
    }

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['tournament_name'], ['game_ref'], ['min_players'], ['max_players'], ['duration_minutes'], ['start_format'], ['category']],
                'lengthMin' => [['tournament_name', 3], ['game_ref', 3]],
                'min' => [['min_players', 0], ['duration_minutes', 1], ['mtt_reg_duration_minutes', 0]],
            ]
        ];
    }

    public function validate()
    {
        $parent_validate_result = parent::validate();

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        if ($this->min_players > $this->max_players) {
            $validator->error('min_players', "Min Players can't be higher than Max Players.");
        }

        if ($this->min_bet > $this->max_bet) {
            $validator->error('min_bet', "Min Bet can't be higher than Max Bet.");
        }

        if ($this->start_format == 'mtt') {
            if ($this->duration_minutes < $this->mtt_late_reg_duration_minutes) {
                $validator->error('mtt_late_reg_duration_minutes', "Mtt Late Reg Duration can't be longer than duration of the Tournament.");
            }
        }

        if (!$validator->validate()) {
            $this->appendErrors($validator->errors());
            return false;
        }

        return $parent_validate_result;
    }

    public function toSearchResult() {
        $game_name = empty($this['game']) ? "" : "{$this['game']['game_name']} - ";
        return [
            'id' => $this['id'],
            'text' => "{$this['id']} - {$game_name}{$this['category']} - {$this['start_format']}"
        ];
    }

    public function getRegLimExcludedCountriesAttribute($value)
    {
        return explode(" ", $value);
    }

}
