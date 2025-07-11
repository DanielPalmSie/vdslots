<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 27/09/16
 * Time: 09:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\TournamentTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class TournamentTemplateRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * TournamentTemplateRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getTournamentTemplateSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'                            => 'Id',
            'game_ref'                      => 'Game Ref',
            'tournament_name'               => 'Tournament Name',
            'category'                      => 'Category',
            'start_format'                  => 'Start Format',
            'win_format'                    => 'Win Format',
            'play_format'                   => 'Play Format',
            'cost'                          => 'Cost',
            'pot_cost'                      => 'Pot Cost',
            'xspin_info'                    => 'XSpin Info',
            'min_players'                   => 'Min Players',
            'max_players'                   => 'Max Players',
            'mtt_show_hours_before'         => 'Mttt Show Hours Before',
            'duration_minutes'              => 'Duration Minutes',
            'mtt_start_time'                => 'Mtt Start Time',
            'mtt_start_date'                => 'Mtt Start Date',
            'mtt_reg_duration_minutes'      => 'Mtt Reg Duration Minutes',
            'mtt_late_reg_duration_minutes' => 'Mtt Late Reg Duration Minutes',
            'mtt_recur_type'                => 'Mtt Recur Type',
            'mtt_recur_days'                => 'Mtt Recur Days',
            'recur_end_date'                => 'Recur End Date',
            'recur'                         => 'Recur',
            'guaranteed_prize_amount'       => 'Guaranteed Pize Amount',
            'prize_type'                    => 'Prize Type',
            'max_bet'                       => 'Max Bet',
            'min_bet'                       => 'Min Bet',
            'house_fee'                     => 'House Fee',
            'get_race'                      => 'Get Race',
            'get_loyalty'                   => 'Get Loyalty',
            'get_trophy'                    => 'Get Trophy',
            'rebuy_times'                   => 'Rebuy Times',
            'rebuy_cost'                    => 'Rebuy Cost',
            'turnover_threshold'            => 'Turnover Threshold',
            'award_ladder_tag'              => 'Award Ladder Tag',
            'duration_rebuy_minutes'        => 'Duration Rebuy Minutes',
            'ladder_tag'                    => 'Ladder Tag',
            'included_countries'            => 'Included Countries',
            'excluded_countries'            => 'Excluded Countries',
            'reg_wager_lim'                 => 'Reg Wager Limit',
            'reg_dep_lim'                   => 'Reg Dep Limit',
            'reg_lim_period'                => 'Reg Lim Period',
            'reg_lim_excluded_countries'    => 'Reg Lim Excluded Countries',
            'free_pot_cost'                 => 'Free Pot Cost',
            'prize_calc_wait_minutes'       => 'Prize Calc Wait Minutes',
            'allow_bonus'                   => 'Allow Bonus',
            'total_cost'                    => 'Total Cost',
            'rebuy_house_fee'               => 'Rebuy House Fee',
            'spin_m'                        => 'Spin_m',
            'pwd'                           => 'pwd',
            'number_of_jokers'              => 'Number Of Jokers',
            'bounty_award_id'               => 'Bounty Award Id',
            'bet_levels'                    => 'Bet Levels',
            'queue'                         => 'Queue'
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'tournament_name', 'game_ref', 'category', 'win_fomat', 'prize_type', 'cost', 'min_bet', 'max_bet', 'rebuy_house_fee', 'rebuy_times'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $trophies_list
     * @return Builder
     */
    public function getTournamentTemplateSearchQuery(Request $request, $archived = false, $trophies_list = null)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('tournament_tpls AS t');
        } else {
            $query = DB::table('tournament_tpls AS t');
        }

        if (!empty($trophies_list) && count($trophies_list) > 0) {
            return $query->whereIn('t.id', $trophies_list);
        }

        $form_elem    = [];
        $extra_select = [];

        if (!empty($request->get('form'))) {
            foreach ($request->get('form') as $key => $val) {
                $form_elem[key($val)][key(array_values($val)[0])] = array_values(array_values($val)[0])[0];
            }
        } else {
            $form_elem = [
                'alias' => $request->get('alias'),
            ];
        }

        $uds_join = false;
        $us_join  = false;
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

        $columns = $this->getTournamentTemplateSearchColumnsList();
        $str = implode(", ", array_keys($columns['select']));

        /*
        foreach ($columns['select'] as $key => $value) {
            $str .= "t.{$key}, ";
        }
        */

        if ($archived) {
            //$query->selectRaw("{$str}t.alias AS backend, t.id AS playcheck, 'Yes' AS archived");
            $query->selectRaw("{$str}");
        } else {
            //$query->selectRaw("{$str}t.alias AS backend, t.id AS playcheck, '' AS archived");
            $query->selectRaw("{$str}");
        }

        if ($grouped) {
            $query->groupBy('t.id');
        }

        return $query;
    }

    /**
     * @return TournamentTemplate
     */
    public function getTournamentTemplateById($id)
    {
        return TournamentTemplate::where('id', $id)->first();
    }

    public function setTournamentTemplateImage(&$tournament)
    {
        $tournament['img'] = phive('Tournament')->getTournamentUri($tournament);
    }

}
