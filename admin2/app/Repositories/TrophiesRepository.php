<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 27/09/16
 * Time: 09:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\IpLog;
use App\Models\Trophy;
use App\Models\TrophyAwards;
use App\Models\User;
use App\Models\UserComment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Symfony\Component\HttpFoundation\Request;

class TrophiesRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * TrophiesRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getAllTrophiesByAlias($alias)
    {
        return Trophy::where('alias', 'LIKE', $alias.'%')->get();
    }

    public function getAllTrophiesByGameRef($game_ref)
    {
        return Trophy::where('game_ref', '=', $game_ref)->get();
    }

    public function getTrophiesSearchColumnsList($table_only = false)
    {
        $columns = [];

        $select = [
            'id'            => 'Id',
            'alias'         => 'Alias',
            'subtype'       => 'Subtype',
            'type'          => 'Type',
            'threshold'     => 'Threshold',
            'time_period'   => 'Time Period',
            'time_span'     => 'Time Span',
            'game_ref'      => 'Game Ref',
            'in_row'        => 'In Row',
            'category'      => 'Category',
            'sub_category'  => 'Sub Category',
            'hidden'        => 'Hidden',
            'amount'        => 'Amount',
            'award_id'      => 'Award Id',
            'award_id_alt'  => 'Award Id Alt',
            'trademark'     => 'Trademark',
            'repeatable'    => 'Repeatable',
            'valid_from'    => 'Valid From',
            'valid_to'      => 'Valid To',
            'completed_ids' => 'Completed Ids',
            'included_countries' => 'Included Countries',
            'excluded_countries' => 'Excluded Countries'
        ];

        if (!$table_only) {
            $select['award_type'] = 'Award Type';
            $select['award_amount'] = 'Award Amount';
            $select['award_description'] = 'Award Description';
            $select['award_alt_type'] = 'Award Alt Type';
            $select['award_alt_amount'] = 'Award Alt Type Amount';
            $select['award_alt_description'] = 'Award Alt Description';
        }

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'alias', 'subtype', 'type', 'category', 'sub_category', 'game_ref'];
        $columns['default_visibility_trophyset'] = ['alias', 'game_ref', 'category', 'sub_category', 'award_id', 'award_id_alt'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $trophies_list
     * @return mixed
     */
    public function getTrophySearchQuery(Request $request, $archived = false, $trophies_list = null)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('trophies AS t');
        } else {
            $query = DB::table('trophies AS t');
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

        foreach ($form_elem['alias'] as $key => $val) {
            if (!empty($val)) {
                if ($key == 'id') {
                    if (strpos($val, ',')) {
                        $query->whereIn('t.id', explode(',', $val));
                    } else {
                        $query->where('t.id', $val);
                    }
                } elseif ($key == 'username') {
                    $query->where('t.username', 'LIKE', '%' . $val . '%');
                } elseif (in_array($key, ['firstname', 'lastname', 'email', 'bonus_code', 'mobile', 'alias'])) {
                    $query->where("t.$key", 'LIKE', '%' . $val . '%');
                } else {
                    $query->whereRaw("t.$key = '$val'");
                }
            }
        }

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

        $columns = $this->getTrophiesSearchColumnsList(true);
        $str = collect(array_keys($columns['select']))
            ->map(function($column) {
                return 't.' . $column;
            })
            ->merge([
                'a.type as award_type',
                'a.amount as award_amount',
                'a.description as award_description',
                'alt.type as award_alt_type',
                'alt.amount as award_alt_amount',
                'alt.description as award_alt_description',
            ])
            ->implode(',');

        $query->leftJoin('trophy_awards as a', 'a.id', '=', 't.award_id');
        $query->leftJoin('trophy_awards as alt', 'alt.id', '=', 't.award_id_alt');

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

    public function getTrophiesList(User $user, $date_range)
    {
        $user_trophy_query = ReplicaDB::shTable($user->getKey(), 'trophy_events AS te')
            // Make sure it's "alias" and "finished" here so this works with phive('Trophy')->getTrophyUri(...).
            ->selectRaw('t.alias AS alias, te.created_at AS te_created_at, te.updated_at AS te_updated_at, te.trophy_type AS te_type, te.finished AS finished, te.progr AS te_progress, te.threshold AS te_thershold, t.*, mg.game_name')
            ->join('trophies AS t', 't.id', '=', 'te.trophy_id')
            ->leftJoin('micro_games AS mg', 't.game_ref', '=', 'mg.ext_game_name')
            ->where('te.user_id', $user->getKey())
            ->groupBy('te.id')
            ->orderBy('te.updated_at', 'DESC');

        if (empty($date_range['end_date'])) {
            $user_trophy_query->where('te.updated_at', '>', $date_range['start_date']);
        } else {
            $user_trophy_query->whereBetween('te.updated_at', [$date_range['start_date'], $date_range['end_date']]);
        }

        return $user_trophy_query->get();
    }

    public function getRewardHistoryList(User $user, $date_range)
    {
        $rewards_query = DB::shTable($user->getKey(), 'trophy_award_ownership AS tao')
            ->leftJoin('trophy_awards AS ta', 'ta.id', '=', 'tao.award_id')
            ->where('tao.user_id', $user->getKey())
            ->where('tao.status', '>', 0)
            ->where('tao.created_at', '>', $date_range['start_date'])
            ->orderBy('tao.created_at', 'DESC');

        if (!empty($date_range['end_date'])) {
            $rewards_query->where('tao.created_at', '<', $date_range['end_date']);
        }

        return $rewards_query->get();
    }

    /**
     * @param User $user
     * @param DateRange $date_range
     * @param Request $request
     * @return mixed
     */
    public function getNotActivatedRewardsList(User $user, DateRange $date_range, Request $request)
    {
        $rewards_query = DB::shTable($user->getKey(), 'trophy_award_ownership AS tao')
            ->selectRaw('tao.id AS tao_id, tao.status, tao.expire_at, tao.finished_at, tao.activated_at, tao.created_at AS rewarded_at, ta.*, mg.game_name, bt.reward, bt.rake_percent')
            ->leftJoin('trophy_awards AS ta', 'ta.id', '=', 'tao.award_id')
            ->leftJoin('bonus_types AS bt', 'bt.id', '=', 'ta.bonus_id')
            ->leftJoin('micro_games AS mg', 'bt.game_id', '=', 'mg.game_id')
            /*->leftJoin('users_comments AS uc', function ($join) {
                $join->on('uc.tag', '=', DB::raw("'trophy_award_ownership'"));
                $join->on('uc.foreign_id', '=', 'trophy_award_ownership.id');
            })*/
            ->where('tao.user_id', $user->getKey())
            ->where('tao.status', 0)
            ->orderBy('tao.created_at', 'DESC')
            ->groupBy('tao_id');

        if (!empty($date_range->getStart())) {
            $rewards_query->where('tao.created_at', '>', $date_range->getStart('timestamp'));
        }
        if (!empty($date_range->getEnd())) {
            $rewards_query->where('tao.created_at', '<', $date_range->getEnd('timestamp'));
        }
        if ($request->get('results', 25) != 'all') {
            $rewards_query->limit($request->get('results', 25));
        }

        $res = collect($rewards_query->get());

        $comments = UserComment::sh($user->getKey())->select('comment', 'foreign_id as tao_id')
            ->where(['user_id' => $user->getKey(), 'tag' => 'trophy_awards_ownership'])
            ->whereIn('foreign_id', $res->pluck('tao_id')->toArray())
            ->get()->keyBy('tao_id');

        return $res->each(function ($item, $key) use ($comments) {
            if (isset($comments[$item->tao_id])) {
                $item->comment = $comments[$item->tao_id]->comment;
            }
        });
    }

    //todo this is a quick port from phive should be good thing to do it properly in the future
    public function getCategories()
    {
        $res = DB::table('trophies as t')
            ->leftJoin('micro_games as mg', 'mg.ext_game_name', '=', 't.sub_category')
            ->groupBy('category')
            ->get();

        $col = 'category';
        foreach ($res as &$v) {
            $key = $v->category;
            $ret[$key] = (empty($v->game_name) || $col == 'category') ? t("trophy.{$v->category}") : $v->game_name;
        }
        asort($ret);
        return $ret;
    }

    public function getPerCategory(User $user, $category)
    {
        $query_res = Trophy::selectRaw('trophies.*')->where('trophies.category', $category)
            ->leftJoin('micro_games', 'micro_games.ext_game_name', '=', 'trophies.sub_category')
            ->whereRaw("(micro_games.id IS NULL OR (micro_games.blocked_countries NOT LIKE '%{$user->country}%' OR micro_games.included_countries LIKE '%{$user->country}%'))")
            ->get();

        return $query_res;
    }

    /**
     * @return array
     */
    public function getRewardsTypes()
    {
        /** @var Collection $col */
        $col = TrophyAwards::selectRaw('DISTINCT type')->get();
        $reward_types = [];
        foreach ($col->pluck('type')->all() as $r) {
            if (p("reward.$r")) {
                $reward_types[$r] = $r;
            }
        }
        return $reward_types;
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getRewardsByType($type)
    {
        return TrophyAwards::where('type', $type)->get();
    }

    /**
     * @param string $type
     * @param string $search
     * @return mixed
     */
    public function getRewardsByTypeSelect(string $type, string $search = '')
    {
        $rewards = TrophyAwards::select('id', 'description')->where('type', $type);
        if (!empty($search)) {
            $rewards = $rewards->where('description', 'LIKE', "%$search%");
        }
        return $rewards->paginate();
    }

    public function addReward(Request $request, User $user)
    {
        $form_elements = $request->request->all();

        $actor = UserRepository::getCurrentUser();

        if (empty($form_elements['award-id']) || empty($form_elements['comment'])) {
            return 'Required elements not filled';
        }

        $ud = cu($user->id)->data;
        $award = phive('Trophy')->giveAward($form_elements['award-id'], $ud);
        if ($award) {
            phive("UserHandler")->logAction(cu($user->id), "give_award");
            phive('UserHandler')->getUser($user->id)->addComment($form_elements['comment'], 0, 'trophy_awards_ownership', $award, 'id');

            $log_message = "{$actor->username} activated award with id {$form_elements["award-id"]} for {$user->username}";
            IpLog::logIp($actor, $user->id, IpLog::TAG_BONUS_ACTIVATING, $log_message);

            $notification = new NotificationRepository($this->app);
            $notification->checkForExceededAllowedAction($user, $actor, "bonus");
        }
        if (empty($award)) {
            return 'Could not add reward!';
        } else {
            return 'Reward was added successfully';
        }
    }

    public function getAddTrophyValidationMessage(User $user, Request $request)
    {
        $trophy_event = DB::shTable($user->getKey(), 'trophy_events AS te')->select(['te.*'])
            ->leftJoin('trophies AS t', 't.id', '=', 'te.trophy_id')
            ->where('te.user_id', $user->getKey())
            ->where('te.trophy_id', $request->get('trophy-id'))
            ->first();

        $trophy = DB::shTable($user->getKey(), 'trophies AS t')->find($request->get('trophy-id'));

        if (Carbon::parse($trophy->valid_from)->isFuture() || Carbon::parse($trophy->valid_to)->isPast()) {
            return "expired.";
        } elseif (!empty($trophy_event->finished)) {
            return "already completed.";
        } else {
            return "system error.";
        }
    }

    /**
     * @return TropyAwards
     */
    public function getTrophyAwardById($id)
    {
        return TrophyAwards::where('id', $id)->first();
    }

    public function setBothTrophyImages(&$trophy)
    {
        $trophy['img_unfinished'] = phive('Trophy')->getTrophyUri($trophy);
        $trophy['finished']       = true;
        $trophy['img_finished']   = phive('Trophy')->getTrophyUri($trophy);
    }

    public function setTrophyImage(&$trophy)
    {
        $trophy['img'] = phive('Trophy')->getTrophyUri($trophy);
    }

}
