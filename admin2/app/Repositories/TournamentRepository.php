<?php

namespace App\Repositories;

use App\Models\Tournament;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class TournamentRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * TournamentRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getTournamentSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'                            => 'Id',
            'tournament_name'               => 'Tournament Name',
            'tpl_id'                        => 'Template Id',
            'game_ref'                      => 'Game Ref',
            'category'                      => 'Category',
            'desktop_or_mobile'             => 'Desktop or Mobile',
            'start_format'                  => 'Start Format',
            'win_format'                    => 'Win Format',
            'play_format'                   => 'Play Format',
            'created_at'                    => 'Created At ',
            'start_time'                    => 'Started At ',
            'mtt_start'                     => 'mtt Start',
            'registered_players'            => 'Registered Players',
            'status'                        => 'Status',
            'pause_calc'                    => 'Paused',
            'prizes_calculated'             => 'Calculated',
            'total_cost'                    => 'Total Cost',
            'cost'                          => 'Cost',
            'duration_minutes'              => 'Duration Minutes',
            'xspin_info'                    => 'Xspin Info',
            'min_bet'                       => 'Min Bet',
            'max_bet'                       => 'Max Bet',
            'house_fee'                     => 'House Fee',
            'min_players'                   => 'Min Players',
            'max_players'                   => 'Max Players',
            'rebuy_house_fee'               => 'Rebuy House Fee',
            'rebuy_cost'                    => 'Rebuy Cost',
            'rebuy_times'                   => 'Rebuy Times',
            'mtt_reg_duration_minutes'      => 'Mtt Reg Duration Minutes',
            'mtt_late_reg_duration_minutes' => 'Mtt Late Reg Duration Minutes',
            'prize_amount'                  => 'Prize Amount',
            'guaranteed_prize_amount'       => 'Guaranteed Prize Amount',
            'prize_type'                    => 'Prize Type',
            'get_race'                      => 'Get Race',
            'get_loyalty'                   => 'Get Loyalty',
            'get_trophy'                    => 'Get Trophy',
            'turnover_threshold'            => 'Turnover Threshold',
            'award_ladder_tag'              => 'Award Ladder Tag',
            'duration_rebuy_minutes'        => 'Duration Rebuy Minutes',
            'ladder_tag'                    => 'Ladder Tag',
            'included_countries'            => 'Included Countries',
            'excluded_countries'            => 'Excluded Countries',
            'blocked_provinces'             => 'Blocked Provinces',
            'reg_wager_lim'                 => 'Reg Wager Limit',
            'reg_dep_lim'                   => 'Reg Dep Limit',
            'reg_lim_period'                => 'Reg Limit Period',
            'reg_lim_excluded_countries'    => 'Reg limit Excluded Countries',
            'pot_cost'                      => 'Pot Cost',
            'free_pot_cost'                 => 'Free Pot Cost',
            'spin_m'                        => 'Spin M',
            'pwd'                           => 'pwd',
            'number_of_jokers'              => 'Number Of Jokers',
            'bounty_award_id'               => 'Bounty Award Id',
            'bet_levels'                    => 'Bet Levels',
            'calc_prize_stamp'              => 'Calc Prize Stamp',
            'allow_bonus'                   => 'Allow Bonus'        ];

        $columns['list']               = $select;
        $columns['select']             = $select;
        $columns['default_visibility'] = ['id', 'tournament_name', 'game_ref', 'category','status','registered_players','min_bet','max_bet','cost','prize_type','rebuy_times'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @return Builder
     */
    public function getTournamentSearchQuery(Request $request, $archived = false)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('tournaments AS t');
        } else {
            $query = DB::table('tournaments AS t');
        }

        $form_elem    = [];
        
        if (!empty($request->get('form'))) {
            foreach ($request->get('form') as $key => $val) {
                $form_elem[key($val)][key(array_values($val)[0])] = array_values(array_values($val)[0])[0];
            }
        } else {
            $form_elem = [
                'alias' => $request->get('alias'),
            ];
        }
        $grouped  = false;

        foreach ($form_elem['since'] as $key => $val) {
            if (!empty($val)) {
                $query->where("t.$key", '>=', $val);
            }
        }

        foreach ($form_elem['before'] as $key => $val) {
            if (!empty($val)) {
                $query->where("t.$key", '<', $val);
            }
        }

        $columns = $this->getTournamentSearchColumnsList();
        $str = implode(", ", array_keys($columns['select']));

        if ($archived) {
            $query->selectRaw("{$str}");
        } else {
            $query->selectRaw("{$str}");
        }

        if ($grouped) {
            $query->groupBy('t.id');
        }

        return $query;
    }

    /**
     * @return Tournament
     */
    public function getTournamentById($id)
    {
        return Tournament::where('id', $id)->first();
    }

}
