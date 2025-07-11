<?php

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\BonusType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class BonusTypeRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * BonusTypesRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getBonusTypeSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'                      => 'Id',
            'expire_time'             => 'Expire Time',
            'num_days'                => 'Number of Days',
            'cost'                    => 'Cost',
            'reward'                  => 'Reward',
            'bonus_name'              => 'Bonus Name',
            'deposit_limit'           => 'Deposit Limit',
            'rake_percent'            => 'Rake Percent',
            'bonus_code'              => 'Bonus Code',
            'bonus_type'              => 'Bonus Type',
            'exclusive'               => 'Exclusive',
            'bonus_tag'               => 'Bonus Tag',
            'type'                    => 'Type',
            'game_tags'               => 'Game Tags',
            'cash_percentage'         => 'Cash Percentage',
            'max_payout'              => 'May Payout',
            'reload_code'             => 'Reload Code',
            'excluded_countries'      => 'Excluded Countries',
            'deposit_amount'          => 'Deposit Amount',
            'deposit_max_bet_percent' => 'Deposit Max Bet Percent',
            'bonus_max_bet_percent'   => 'Bonus Max Bet Percent',
            'max_bet_amount'          => 'Max Bet Amount',
            'included_countries'      => 'Included Countries',
            'fail_limit'              => 'Fail Limit',
            'game_percents'           => 'Game Percents',
            'loyalty_percent'         => 'Loyalty Percents',
            'top_up'                  => 'Top Up',
            'stagger_percent'         => 'Stagger Percent',
            'ext_ids'                 => 'External IDs',
            'progress_type'           => 'Progress Type',
            'deposit_threshold'       => 'Desposit Threshold',
            'game_id'                 => 'Game ID',
            'allow_race'              => 'Allow Race',
            'forfeit_bonus'           => 'Forfeit Bonus',
            'deposit_active_bonus'    => 'Deposit Active Bonus',
            'frb_coins'               => 'Free Spins Bet Coins',
            'frb_denomination'        => 'Free Spins Bet Denomination',
            'frb_lines'               => 'Free Spins Bet Lines',
            'frb_cost'                => 'Free Spins Bet Cost',
            'award_id'                => 'Award ID',
            'keep_winnings'           => 'Keep Winnings',
            'allow_xp_calc '          => 'Allow XP points calculation'
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'expire_time', 'num_days', 'cost', 'reward', 'bonus_name', 'deposit_limit'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $bonus_types_list
     * @return Builder
     */
    public function getBonusTypeSearchQuery(Request $request, $archived = false, $bonus_types_list = null)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('bonus_types AS t');
        } else {
            $query = DB::table('bonus_types AS t');
        }

        if (!empty($bonus_types_list) && count($bonus_types_list) > 0) {
            return $query->whereIn('t.id', $bonus_types_list);
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

        $columns = $this->getBonusTypeSearchColumnsList();
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
     * @return BonusType
     */
    public function getBonusTypeById($id)
    {
        return BonusType::where('id', $id)->first();
    }


}
