<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 24/06/2016
 * Time: 15:27
 */

namespace App\Repositories;

use App\Constants\Networks;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Models\User;
use App\Traits\BetsQueryTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use PDO;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;

//TODO this class need to be refactored
class BetsAndWinsRepository
{
    use BetsQueryTrait;

    /** @var Application $app */
    protected $app;


    /**
     * UserProfileRepository constructor.
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     */
    public function __construct(Application $app, User $user, Request $request)
    {
        $this->app = $app;
        $this->user = $user;
        $this->request = $request;
    }

    /**
     * @param Builder $non_archived_query
     * @param Builder $archived_query
     * @return Collection|\Illuminate\Support\Collection
     */
    public function mergeArchived($non_archived_query, $archived_query)
    {
        if ($archived_query == 0) {
            return $non_archived_query->get();
        }
        try {
            $archived_res = $archived_query->get();
            $archive_count = count($archived_res);
        } catch (\Exception $e) {
            $this->app['monolog']->addError("[BO-ARCHIVE] Merge archive failed: {$e->getMessage()}");
            $archive_count = 0;
            $archived_res = [];
        }
        $non_archived_res = $non_archived_query->get();
        if ($archive_count > 0) {
            return $non_archived_res->merge($archived_res);
        } else {
            return $non_archived_res;
        }
    }

    /**
     * $bets_join
     * @return Builder
     */
    public function getBetListQuery($archived = false, $bets_join = false)
    {
        $this->processBetWinQueryData($archived);
        $date_range = [$this->query_data['start_date'], $this->query_data['end_date']];

        $query = $archived ? $this->bets_archived_query : $this->bets_query;
        $join_type = $bets_join ? 'join' : 'leftJoin';
        $query->select("{$this->bets_table_name}.id", 'game_name', 'trans_id', 'amount', 'game_ref', 'created_at', 'mg_id', 'balance', 'bonus_bet', 'currency', 'loyalty', 'jp_contrib', 'mg1.id as g_id')
            ->{$join_type}('micro_games as mg1', function ($join) {
                $join->on("{$this->bets_table_name}.game_ref", '=', 'mg1.ext_game_name');
                $join->on("{$this->bets_table_name}.device_type", '=', 'mg1.device_type_num');
            })
            ->whereBetween('created_at', $date_range);

        if (empty($this->query_data['custom_order'])) {
            $query->orderBy('created_at', $this->query_data['order']);
        }

        if ($this->query_data['bonus'] == "no") {
            $query->where('bonus_bet', 0);
        } elseif ($this->query_data['bonus'] == "yes") {
            $query->whereIn('bonus_bet', [1,3]);
        }

        if (!empty($this->query_data['game']) && $this->query_data['game'] != 'all') {
            $query->where('game_ref', $this->query_data['game']);
        }elseif (!empty($this->query_data['operator'])) {
            $game_repo = new GameRepository();
            $operator = explode("::", ($this->query_data['operator']));
            $g = $game_repo->getGamesByOperator($operator[0], $operator[1]);
            $query->whereIn('game_ref', $g->pluck('ext_game_name')->toArray());
        }

        // This applies only when we are selecting a game
        if (!empty($this->query_data['device_type'])) {
            $query->where('mg1.device_type', $this->query_data['device_type']);
        }

        return $query;
    }

    /**
     * @param $bet_list
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBetList($bet_list)
    {
        $records[] = ['Date', 'Game', 'Currency', 'Amount', 'Balance', 'Cash Back Balance', 'Bonus Bet', 'ID', 'Transaction ID'];

        foreach ($bet_list as $bet) {
            $records[] = [
                $bet->created_at,
                $bet->game_name,
                $bet->currency,
                $bet->amount / 100,
                $bet->balance / 100,
                $bet->loyalty / 100,
                ($bet->bonus_bet) ? 'Yes' : 'No',
                $bet->mg_id,
                $bet->trans_id
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-bet-list_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * @param $bet_list
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBetListXp($bet_list)
    {
        $records[] = ['Bet ID', 'Date', 'Game', 'Xp Points', 'ID'];
        foreach ($bet_list as $bet) {
            $records[] = [
                $bet->id,
                $bet->created_at,
                $bet->game_name,
                $bet->xp,
                $bet->mg_id
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-bet-list-xp-history_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * @param $archived
     * @return Builder
     */
    public function getBetWinListQuery($archived = false)
    {
        $this->processBetWinQueryData($archived);
        $date_range = [$this->query_data['start_date'], $this->query_data['end_date']];

        $bets_query = $archived ? $this->bets_archived_query : $this->bets_query;

        $bets_query->selectRaw("{$this->bets_table_name}.id as id, game_name, trans_id, amount, game_ref, created_at, mg_id, balance, bonus_bet, currency, loyalty, null as award_type, 'Bet' as type")
            ->leftJoin('micro_games as mg1', function ($join) {
                $join->on("{$this->bets_table_name}.game_ref", '=', 'mg1.ext_game_name');
                $join->on("{$this->bets_table_name}.device_type", '=', 'mg1.device_type_num');
            });

        if ($this->query_data['bonus'] == "no") {
            $bets_query->where('bonus_bet', 0);
        } elseif ($this->query_data['bonus'] == "yes") {
            $bets_query->whereIn('bonus_bet', [1,3]);
        }
        //todo remove the all clause on all that kind of queries and chage the select2 to be able to delete
        if (!empty($this->query_data['game']) && $this->query_data['game'] != 'all') {
            $bets_query->where('game_ref', $this->query_data['game']);
        }elseif (!empty($this->query_data['operator'])) {
            $game_repo = new GameRepository();
            $operator = explode("::", ($this->query_data['operator']));
            $g = $game_repo->getGamesByOperator($operator[0], $operator[1]);
            $bets_query->whereIn('game_ref', $g->pluck('ext_game_name')->toArray());
        }
        // This applies only when we are selecting a game
        if (!empty($this->query_data['device_type'])) {
            $bets_query->where('mg1.device_type', $this->query_data['device_type']);
        }

        $wins_query = $archived ? $this->wins_archived_query : $this->wins_query;
        $wins_query->selectRaw("{$this->wins_table_name}.id as id, game_name, trans_id, amount, game_ref, created_at, mg_id, balance, bonus_bet, currency, null as loyalty, award_type, 'Win' as type")
            ->leftJoin('micro_games as mg2', function ($join) {
                $join->on("{$this->wins_table_name}.game_ref", '=', 'mg2.ext_game_name');
                $join->on("{$this->wins_table_name}.device_type", '=', 'mg2.device_type_num');
            });

        if ($this->query_data['bonus'] == "no") {
            $wins_query->where('bonus_bet', 0);
        } elseif ($this->query_data['bonus'] == "yes") {
            $wins_query->whereIn('bonus_bet', [1,3]);
        }
        if (!empty($this->query_data['game']) && $this->query_data['game'] != 'all') {
            $wins_query->where('game_ref', $this->query_data['game']);
        }elseif (!empty($this->query_data['operator'])) {
            $game_repo = new GameRepository();
            $operator = explode("::", ($this->query_data['operator']));
            $g = $game_repo->getGamesByOperator($operator[0], $operator[1]);
            $wins_query->whereIn('game_ref', $g->pluck('ext_game_name')->toArray());
        }

        // This applies only when we are selecting a game
        if (!empty($this->query_data['device_type'])) {
            $wins_query->where('mg2.device_type', $this->query_data['device_type']);
        }

        if ($this->query_data['mp'] == 1 && $this->request->get('t_id')) {
            $bets_query->where('t_id', $this->request->get('t_id'));
            $wins_query->where('t_id', $this->request->get('t_id'));
        } else {
            $bets_query->whereBetween('created_at', $date_range);
            $wins_query->whereBetween('created_at', $date_range);
        }

        return $bets_query->union($wins_query)
            ->orderBy('created_at', $this->query_data['order']);
    }

    /**
     * @param bool $archived
     * @return Builder
     */
    public function getWinListQuery($archived = false)
    {
        $this->processBetWinQueryData($archived);
        $date_range = [$this->query_data['start_date'], $this->query_data['end_date']];

        $select_columns = [
            "$this->wins_table_name.id",
            'game_name',
            'trans_id',
            "$this->wins_table_name.amount",
            'game_ref',
            'created_at',
            'mg_id',
            "$this->wins_table_name.balance",
            'bonus_bet',
            "$this->wins_table_name.currency",
            'award_type',
        ];
        if (!$archived) {
            $select_columns[] = 'cash_transactions.amount as transferred_to_vault';
        }

        $wins_query = $archived ? $this->wins_archived_query : $this->wins_query;

        $wins_query->select($select_columns)
            ->leftJoin('micro_games as mg2', function ($join) {
                $join->on("{$this->wins_table_name}.game_ref", '=', 'mg2.ext_game_name');
                $join->on("{$this->wins_table_name}.device_type", '=', 'mg2.device_type_num');
            });
        if (!$archived) {
            $wins_query->leftJoin('cash_transactions', function ($join) {
                $join->on("{$this->wins_table_name}.id", '=', 'cash_transactions.parent_id');
                $join->where('cash_transactions.transactiontype', '=', 100);
                $join->where('cash_transactions.user_id', '=', $this->user->getKey());
            });
        }

        $wins_query->whereBetween('created_at', $date_range)
            ->orderBy('created_at', $this->query_data['order']);

        if ($this->query_data['bonus'] == "no") {
            $wins_query->where('bonus_bet', 0);
        } elseif ($this->query_data['bonus'] == "yes") {
            $wins_query->whereIn('bonus_bet', [1,3]);
        }

        if (!empty($this->query_data['game']) && $this->query_data['game'] != 'all') {
            $wins_query->where('game_ref', $this->query_data['game']);
        }elseif (!empty($this->query_data['operator'])) {
            $game_repo = new GameRepository();
            $operator = explode("::", ($this->query_data['operator']));
            $g = $game_repo->getGamesByOperator($operator[0], $operator[1]);
            $wins_query->whereIn('game_ref', $g->pluck('ext_game_name')->toArray());
        }

        // This applies only when we are selecting a game
        if (!empty($this->query_data['device_type'])) {
            $wins_query->where('mg2.device_type', $this->query_data['device_type']);
        }

        return $wins_query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getSportsbookBets(string $ticketId = null)
    {
        $this->processBetWinQueryData();
        $date_range = [$this->query_data['start_date'], $this->query_data['end_date']];

        $query = ReplicaDB::shTable($this->user->getKey(), 'sport_transactions as st')->where('st.user_id', $this->user->getKey());

        $sub_query =  ReplicaDB::shTable($this->user->getKey(), 'sport_transaction_details as std')
            ->selectRaw(
                "created_at,
                GROUP_CONCAT(odds) as odds,
                IF(count(distinct sport) > 1, '', sport) as sport,
                CASE
                    WHEN COUNT(DISTINCT JSON_EXTRACT(event_info,'$.producer_ext_id')) > 1 THEN 'Pre-Match / Live'
                    WHEN JSON_EXTRACT(event_info,'$.producer_ext_id') = 3 THEN 'Pre-Match'
                    WHEN JSON_EXTRACT(event_info,'$.producer_ext_id') = 1 THEN 'Live'
                END AS type,
                sport_transaction_id,
                GROUP_CONCAT(JSON_UNQUOTE(JSON_EXTRACT(event_info,'$.event_date'))) as event_dates"
            )
            ->groupBy('ticket_id');

        $query
            ->select(
                'st.ticket_id as bet_id',
                'win.id as win_id',
                'event_dates',
                'std.sport as game',
                'type',
                'st.ticket_type',
                'st.amount as bet_amount',
                'std.created_at as bet_date',
                'st.bet_placed_at as bet_placed_date',
                'st.balance as end_balance',
                'odds',
                'win.amount as actual_win'
            )
            ->leftJoin('sport_transactions as win', function ($join) {
                $join
                    ->on('win.ticket_id', '=', 'st.ticket_id')
                    ->where('win.product', Networks::BETRADAR['product'])
                    ->where('win.network', Networks::BETRADAR['name'])
                    ->whereIn('win.bet_type', ['win', 'void'])
                    ->whereNull('win.ignore_sportsbook_history');

            })
            ->join(ReplicaDB::raw('(' . $sub_query->toSql(). ') as std'),'std.sport_transaction_id', '=', 'st.id')
            ->where('st.bet_type', '=', 'bet')
            ->where('st.product', Networks::BETRADAR['product'])
            ->where('st.network', Networks::BETRADAR['name'])
            ->whereNull('st.ignore_sportsbook_history');

        if ($ticketId) {
            $query = $query->where('st.ticket_id', $ticketId)->latest('st.id');
        } else {
            $query = $query->whereBetween('st.created_at', $date_range);
        }

        $query = $query->orderBy('st.created_at', $this->query_data['order']);

        $result = $query->get();

        $result = $result->map(function ($item, $key) {

            if($item->ticket_type === 'multi') {
                $item->ticket_type = 'multibet';
            }
            $odds = collect(explode(',', $item->odds));
            $item->odds = $odds->reduce(function($carry, $item) {
                return $carry * $item;
            }, 1);

            if($item->event_dates !== null) {
                try {
                    $item->event_dates = (new \DateTime(explode(',', $item->event_dates)[0]))->format('Y-m-d H:i:s');
                } catch(\Exception $e) {
                    $item->event_dates = null;
                }
            }


            $item->potential_win = $item->bet_amount * $item->odds;
            return $item;
        });

        return $result;
    }

    /**
     * @param int $bet_id
     * @return \Illuminate\Support\Collection
     */
    public function getSportsbookBetDetails(int $bet_id)
    {
        /** @var Builder */
        $query = ReplicaDB::shTable($this->user->getKey(), 'sport_transaction_details as std')->where('std.user_id', $this->user->getKey());

        $query->selectRaw(
                "
                event_ext_id,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.event_date')) as event_date,
                competitors,
                outcome_id,
                specifiers,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.market_ext_id')) as market,
                odds,
                void_factor,
                result,
                market as market_name,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.event_original_name')) as event_original_name,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.outcome_competitor_original_name')) as outcome_competitor_original_name,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.auto_accepted_odds.odds_change_applied')) as odds_change_applied,
                JSON_UNQUOTE(JSON_EXTRACT(event_info, '$.auto_accepted_odds.requested_odds')) as requested_odds"
        )
            ->where('ticket_id', '=', $bet_id);

        $result = $query->get();

        $result = $result->map(function ($item, $key) {

            $event_ext_id = explode(':', $item->event_ext_id);
            $item->match_id = end($event_ext_id);

            if($item->event_date !== null) {
                try {
                    $item->event_date = (new \DateTime($item->event_date))->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $item->event_date = null;
                }
            }

            $teams = [];
            foreach (json_decode($item->competitors) as $competitor) {
                $key = $competitor->competitor_qualifier === 'home' ? 'competitor1' : 'competitor2';
                $teams[$key] = $competitor->competitor_name;
            }

            $item->event = $teams['competitor1'] . ' vs ' . $teams['competitor2'];


            $item->selection = phive('Sportsbook')->translateStringUsingSpecifiersAndCompetitors(
                "sb.market_outcome.{$item->outcome_id}.name",
                $item->specifiers,
                $teams
            );

            if($item->market !== null) {
                $item->market = phive('Sportsbook')->translateStringUsingSpecifiersAndCompetitors(
                    "sb.market.{$item->market}.name",
                    $item->specifiers,
                    $teams
                );
            }

            $item->result = $this->getSelectionStatus($item->result, $item->void_factor);

            /* If event has event_original_name it's mean what this is Outright event */
            if ($item->event_original_name) {
                $item->event = $item->event_original_name;
                $item->market = $item->market_name;
                /* In case of competitor outcome */
                if ($item->outcome_competitor_original_name) {
                    $item->selection = $item->outcome_competitor_original_name;
                }
            }

            return $item;
        });

        return $result;
    }

    /**
     * Return string representation of sportsbook bet result
     * @param int|null $result
     * @param float|null $void_factor
     * @return string
     */
    private function getSelectionStatus(?int $result, ?float $void_factor)
    {
        if ($result !== null) {
            if ($result === 1) {
                return 'win';
            }

            if ($void_factor !== null && $void_factor > 0) {
                return 'void';
            }

            return 'lost';
        }

        return 'open';
    }


    /**
     * @param $win_list
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportWinList($win_list)
    {
        $records[] = ['Date', 'Game', 'Currency', 'Amount', 'Balance', 'Bonus Bet', 'ID', 'Transaction ID', 'Award Type'];

        foreach ($win_list as $win) {
            $records[] = [
                $win->created_at,
                $win->game_name,
                $win->currency,
                $win->amount / 100,
                $win->balance / 100,
                ($win->bonus_bet) ? 'Yes' : 'No',
                $win->mg_id,
                $win->trans_id,
                DataFormatHelper::getWinType($win->award_type)
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-win-list_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * @param $bet_win_list
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBetWinList($bet_win_list)
    {
        $records[] = ['Type', 'Date', "Amount ({$this->user->currency})",'Game', "Balance ({$this->user->currency})", 'Bonus bet', 'ID', 'Transaction ID', 'Trans type'];

        foreach ($bet_win_list as $bet_win) {
            $records[] = [
                $bet_win->type,
                $bet_win->created_at,
                $bet_win->amount / 100,
                $bet_win->game_name,
                $bet_win->balance / 100,
                ($bet_win->bonus_bet) ? 'Yes' : 'No',
                $bet_win->mg_id,
                $bet_win->trans_id,
                DataFormatHelper::getWinType($bet_win->award_type)
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-bet-and-win-list_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * @param \Illuminate\Support\Collection $sportsbook_bets
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportSportsbookBetList($sportsbook_bets)
    {
        $records[] = ['Bet ID', 'Win ID', "Event Date", "Bet Date", 'Sport', "Type", 'Bet Type', "Bet Amount ({$this->user->currency})", "Win Amount ({$this->user->currency})", 'End Balance', 'Odds', 'Actual Win'];

        foreach ($sportsbook_bets as $bet) {
            $records[] = [
                $bet->bet_id,
                $bet->win_id,
                $bet->event_dates,
                $bet->bet_date,
                $bet->game,
                $bet->type,
                ucfirst($bet->ticket_type),
                $bet->bet_amount / 100,
                'Potential Win: ' . $bet->potential_win / 100,
                $bet->end_balance / 100,
                'Total Odd: ' .  $bet->odds,
                $bet->actual_win / 100
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-sportsbook-bets-list_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * @return Builder
     */
    public function getTransactionsListQuery()
    {
        $this->processBetWinQueryData();
        $date_range = [$this->query_data['start_date'], $this->query_data['end_date']];

        /** @var Builder $trans_query */
        $trans_query = $this->user->cashTransactions()
            ->whereBetween('timestamp', $date_range)
            ->whereIn('transactiontype', [1, 2, 3, 4, 5, 7, 8 ,9, 12, 13, 14, 15])
            ->orderBy('timestamp', $this->query_data['order']);

        return $trans_query;
    }

    /**
     * @param $win_list
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportTransactionList($win_list)
    {
        $records[] = ['Date', 'Game', 'Currency', 'Amount', 'Balance', 'Bonus Bet', 'ID', 'Transaction ID', 'Award Type'];

        foreach ($win_list as $win) {
            $records[] = [
                $win->created_at,
                $win->game_name,
                $win->currency,
                $win->amount / 100,
                $win->balance / 100,
                ($win->bonus_bet) ? 'Yes' : 'No',
                $win->mg_id,
                $win->trans_id,
                DataFormatHelper::getWinType($win->award_type)
            ];
        }

        return DownloadHelper::streamAsCsv(
            $this->app,
            $records,
            "{$this->user->username}-transaction-list_{$this->query_data['start_date']}_to_{$this->query_data['end_date']}"
        );
    }

    /**
     * This will insert into "users_daily_unique_bets" each unique bet amounts per user per day.
     *
     * @param Carbon $carbon_date
     * @return array{success: bool, message?: string}
     */
    public static function cacheUsersDailyUniqueBets(Carbon $carbon_date)
    {
        $start_date = $carbon_date->copy()->startOfDay()->toDateTimeString();
        $end_date = $carbon_date->copy()->endOfDay()->toDateTimeString();
        $date = $carbon_date->copy()->toDateString();

        $delete_sql = "DELETE FROM users_daily_unique_bets where date = '$date';";

        DB::loopNodes(function (Connection $connection) use ($delete_sql)  {
            $connection->statement($delete_sql);
        });

        ReplicaDB::loopNodes(function (Connection $connection) use ($date, $start_date, $end_date)  {
            echo "{$connection->getName()} \n";

            $connection->setFetchMode(PDO::FETCH_ASSOC);

            $list = $connection->select("
                    SELECT user_id,
                           amount,
                           COUNT(*) AS count,
                           '{$date}' AS `date`
                    FROM bets
                    WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY user_id, amount;
            ");

            try {
                DB::bulkInsert('users_daily_unique_bets', 'user_id', $list);
            } catch (\Exception $exception) {
                return ['success' => false, 'message' => "Exception during bulkInsert(), {$exception->getMessage()}"];
            }
        });

        return ['success' => true, 'message' => ''];
    }

    public function getGameHistoryBetsBuilder(): Builder
    {
        return DB::shTable($this->user->getKey(), 'bets')
            ->select('created_at', 'amount', 'game_ref', 'currency', 'micro_games.game_name as game_name')
            ->where('user_id', $this->user->getKey())
            ->leftJoin('micro_games', function ($join) {
                $join->on('bets.game_ref', '=', 'micro_games.ext_game_name');
                $join->on('bets.device_type', '=', 'micro_games.device_type_num');
            });
    }

    public function getGameHistoryWinsBuilder(): Builder
    {
        return DB::shTable($this->user->getKey(), 'wins')
            ->select('created_at', 'amount', 'game_ref', 'currency', 'micro_games.game_name as game_name')
            ->where('user_id', $this->user->getKey())
            ->leftJoin('micro_games', function ($join) {
                $join->on('wins.game_ref', '=', 'micro_games.ext_game_name');
                $join->on('wins.device_type', '=', 'micro_games.device_type_num');
            });
    }
}
