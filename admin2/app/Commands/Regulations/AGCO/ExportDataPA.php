<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportDataPA extends ExportBaseClass
{
    protected string $report_name = 'PA';

    private array $initial_row = [];
    private array $bets = [];
    private array $bets_mp = [];
    private array $bonus_entries = [];
    private array $wins = [];
    private array $cash_transactions = [];
    private array $deposits = [];
    private array $high_risk = [];
    private array $user_game_sessions = [];
    private array $tournament_game_sessions = [];
    private array $action_break_status = [];
    private array $action_se_status = [];
    private array $withdrawable_wins = [];
    private array $bet_rollbacks = [];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("export:pa")
            ->setDescription('Export data for PA generation in reporting service')
            ->addArgument(
                "start_date",
                InputArgument::OPTIONAL,
                "Start date for the export, by default its Sunday from 2 weeks ago",
                Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d')
            )
            ->addArgument(
                "end_date",
                InputArgument::OPTIONAL,
                "End date for the export, by default its previous Saturday",
                Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d')
            )
            ->addArgument(
                "brand",
                InputArgument::OPTIONAL,
                "Brand, by default its videoslots",
                static::BRAND_VIDEOSLOTS
            )
            ->addArgument(
                "file_path",
                InputArgument::OPTIONAL,
                "Full file path where the CSV should stored (example: `/var/www/admin2/storage/AGCO/GGR`)",
                getenv('STORAGE_PATH') . "/" . static::STORAGE_PATH . "/" . $this->report_name
            )
            ->addArgument(
                "file_name",
                InputArgument::OPTIONAL,
                "Name of the CSV to be generated (without `.csv` extension), default convention is `report-name_gaming-site-id_start-date`(pa_S100062A_2023-11-01)"
            )
            ->addOption(
                "without-headers",
                null,
                InputOption::VALUE_OPTIONAL,
                "Remove headers from the CSV file"
            );
    }

    /**
     * @return void
     */
    protected function collectData(): void
    {
        $this->setBetsData();
        $this->setBetsMpData();
        $this->setBonusEntriesData();
        $this->setWinsData();
        $this->setCashTransactionsData();
        $this->setDepositsData();
        $this->setHighRiskData();
        $this->setUserGameSessionsData();
        $this->setTournamentGameSessionsData();
        $this->setActionsBreakStatus();
        $this->setActionsSeStatus();
        $this->setWitdrawableWinsData();
        $this->setBetRollbacksData();
    }

    /**
     * @return void
     */
    protected function setBetsData(): void
    {
        $query = $this->getBetsData();

        $this->bets = array_merge($this->bets, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setBetsMpData(): void
    {
        $query = $this->getBetsMpData();

        $this->bets_mp = array_merge($this->bets_mp, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setBonusEntriesData(): void
    {
        $query = $this->getBonusEntriesData();

        $this->bonus_entries = array_merge($this->bonus_entries, $query->get()->toArray());
    }

    /**
     * @return void
     */
    protected function setWinsData(): void
    {
        $query = $this->getWinsData();

        $this->wins = array_merge($this->wins, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setCashTransactionsData(): void
    {
        $query = $this->getCashTransactionsData();

        $this->cash_transactions = array_merge($this->cash_transactions, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setDepositsData(): void
    {
        $query = $this->getDepositsData();

        $this->deposits = array_merge($this->deposits, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setHighRiskData(): void
    {
        $query = $this->getHighRiskData();

        $this->high_risk = array_merge($this->high_risk, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setUserGameSessionsData(): void
    {
        $query = $this->getUserGameSessionsData();

        $this->user_game_sessions = array_merge($this->user_game_sessions, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setTournamentGameSessionsData(): void
    {
        $query = $this->getTournamentGameSessionsData();

        $this->tournament_game_sessions = array_merge($this->tournament_game_sessions, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setActionsBreakStatus(): void
    {
        $query = $this->getActionsBreakStatus();

        $this->action_break_status = array_merge($this->action_break_status, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setActionsSeStatus(): void
    {
        $query = $this->getActionsSeStatus();

        $this->action_se_status = array_merge($this->action_se_status, $query->get()->toArray());
    }

    /**
     * @return void
     */
    private function setWitdrawableWinsData(): void
    {
        $query = $this->getWithdrawableWinsData();

        $this->withdrawable_wins = array_merge($this->withdrawable_wins, $query->get()->toArray());
    }

    /**
     * @return void
     */
    protected function setBetRollbacksData(): void
    {
        $query = $this->getBetRollbacksData();

        $this->bet_rollbacks = array_merge($this->bet_rollbacks, $query->get()->toArray());
    }

    /**
     * @return Builder
     */
    protected function getBetsData(): Builder
    {
        return $this->connection
            ->table("bets AS b")
            ->select("b.user_id")
            ->selectRaw($this->getDateRawQuery("b.created_at") . " AS gaming_day")
            ->selectRaw("SUM(IF (b.bonus_bet = 0, b.amount, 0)) AS bets")
            ->selectRaw("SUM(IF (b.bonus_bet != 0 AND bonus_bet != 3, b.amount, 0)) AS promo_bets")
            ->selectRaw("SUM(IF (b.bonus_bet = 1, b.amount, 0)) AS promo_adjustments")
            ->selectRaw("SUM(IF (b.bonus_bet != 3, 1, 0)) AS number_of_wagers")
            ->selectRaw("SUM(IF(b.device_type = 0, b.amount, 0)) AS wagers_pc")
            ->selectRaw("SUM(IF(b.device_type IN (1,2,3), b.amount, 0)) AS wagers_mobile")
            ->selectRaw("SUM(IF(mg.tag IN ('blackjack', 'roulette', 'table'), b.amount, 0)) AS wagers_tables")
            ->selectRaw("SUM(IF(mg.tag IN ('casino-playtech','other','slots','slots_tournament','slots_jackpot','system','videoslots','arcade'), b.amount, 0)) AS wagers_slots")
            ->selectRaw("SUM(IF(mg.tag IN ('live', 'live-casino'), b.amount, 0)) AS wagers_live_dealer")
            ->selectRaw("SUM(IF(mg.tag IN ('videopoker','scratch-cards','videoslots_jackpot','videoslots_jackpotbsg','wheel-of-jps'), b.amount, 0)) AS wagers_other")
            ->leftJoin("micro_games AS mg", function ($join) {
                $join
                    ->on('b.game_ref', '=', 'mg.ext_game_name')
                    ->on('b.device_type', '=', 'mg.device_type_num');
            })
            ->whereIn("b.user_id", $this->getUsersQuery())
            ->whereNotIn("b.user_id", $this->getTestUsersQuery())
            ->where("b.amount", ">", 0)
            ->whereBetween(DB::raw($this->getDateRawQuery("b.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "b.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getBetsMpData(): Builder
    {
        return $this->connection
            ->table("bets_mp AS bmp")
            ->select("bmp.user_id")
            ->selectRaw($this->getDateRawQuery("bmp.created_at") . " AS gaming_day")
            ->selectRaw("SUM(bmp.amount) AS promo_bets")
            ->selectRaw("SUM(bmp.amount) AS promo_adjustments")
            ->selectRaw("COUNT(1) as number_of_wagers")
            ->selectRaw("SUM(IF(bmp.device_type = 0, bmp.amount, 0)) AS wagers_pc")
            ->selectRaw("SUM(IF(bmp.device_type IN (1,2,3), bmp.amount, 0)) AS wagers_mobile")
            ->selectRaw("SUM(bmp.amount) AS wagers_slots")
            ->selectRaw("SUM(IF(mg.tag IN ('blackjack', 'roulette', 'table'), bmp.amount, 0)) AS wagers_tables")
            ->selectRaw("SUM(IF(mg.tag IN ('live', 'live-casino'), bmp.amount, 0)) AS wagers_live_dealer")
            ->selectRaw("SUM(IF(mg.tag IN ('videopoker','scratch-cards','videoslots_jackpot','videoslots_jackpotbsg','wheel-of-jps'), bmp.amount, 0)) AS wagers_other")
            ->join("tournaments AS t", "bmp.t_id", "=", "t.id")
            ->leftJoin("micro_games AS mg", function ($join) {
                $join
                    ->on('bmp.game_ref', '=', 'mg.ext_game_name')
                    ->on('bmp.device_type', '=', 'mg.device_type_num');
            })
            ->whereIn("bmp.user_id", $this->getUsersQuery())
            ->whereNotIn("bmp.user_id", $this->getTestUsersQuery())
            ->where("t.category", "freeroll")
            ->where("bmp.bonus_bet", 0)
            ->where("bmp.amount", ">", 0)
            ->whereBetween(DB::raw($this->getDateRawQuery("bmp.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "bmp.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getBonusEntriesData(): Builder
    {
        return $this->connection
            ->table("bonus_entries AS be")
            ->select("be.user_id")
            ->selectRaw($this->getDateRawQuery("be.last_change") . " AS gaming_day")
            ->selectRaw("SUM(bt.frb_cost) AS promo_bets")
            ->selectRaw("SUM(bt.frb_cost) AS promo_adjustments")
            ->selectRaw("SUM(bt.reward) AS number_of_wagers")
            ->selectRaw("SUM(IF(us.equipment IN ('pc', 'macintosh'), bt.frb_cost, 0)) AS wagers_pc")
            ->selectRaw("SUM(IF(us.equipment NOT IN ('pc', 'macintosh'), bt.frb_cost, 0)) AS wagers_mobile")
            ->selectRaw("SUM(IF(mg.tag IN ('blackjack', 'roulette', 'table'), bt.frb_cost, 0)) AS wagers_tables")
            ->selectRaw("SUM(IF(mg.tag IN ('casino-playtech', 'other', 'slots', 'slots_tournament', 'slots_jackpot', 'system', 'videoslots','arcade'), bt.frb_cost, 0)) AS wagers_slots")
            ->selectRaw("SUM(IF(mg.tag IN ('live', 'live-casino'), bt.frb_cost, 0)) AS wagers_live_dealer")
            ->selectRaw("SUM(IF(mg.tag IN ('videopoker', 'scratch-cards', 'videoslots_jackpot', 'videoslots_jackpotbsg', 'wheel-of-jps'), bt.frb_cost, 0)) AS wagers_other")
            ->join("bonus_types AS bt", "bt.id", "=", "be.bonus_id")
            ->leftJoin("users_sessions AS us", "us.id", "=", DB::raw($this->getUserSessionsBonusEntriesSubQuery()))
            ->join("micro_games AS mg", "mg.id", "=", DB::raw($this->getMicroGamesSubQuery()))
            ->whereIn("be.user_id", $this->getUsersQuery())
            ->whereNotIn("be.user_id", $this->getTestUsersQuery())
            ->where("bt.bonus_type", "freespin")
            ->where("be.status", "approved")
            ->whereBetween(DB::raw($this->getDateRawQuery("be.last_change")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "be.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    protected function getWinsData(): Builder
    {
        return $this->connection
            ->table("wins AS w")
            ->select("w.user_id")
            ->selectRaw($this->getDateRawQuery("w.created_at") . " AS gaming_day")
            ->selectRaw("SUM(IF(bonus_bet != 0, amount, 0)) AS withdrawable_win")
            ->selectRaw("SUM(IF(bonus_bet = 0, amount, 0)) AS wins")
            ->whereIn("w.user_id", $this->getUsersQuery())
            ->whereNotIn("w.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("w.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "w.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getCashTransactionsData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->select("ct.user_id")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . " AS gaming_day")
            ->selectRaw("
            SUM(IF(ct.transactiontype = 8,
                (-1) * ct.amount,
                IF(ct.parent_id > 0 AND ct.transactiontype = 13, (-1) * ct.amount, 0)
            )) AS withdrawals")
            ->selectRaw("
            SUM(
                IF(ct.parent_id = 0 AND ct.transactiontype IN (
                    2, 13, 14, 15, 31, 32, 50, 66, 69, 74, 77, 80, 82, 84, 86, 90, 94, 95, 96
                ),
                ct.amount,
                0
            )) AS adjustments")
            ->selectRaw("
                SUM(
                    IF(ct.transactiontype IN (34, 35, 52, 54), (-1) * ct.amount,
                        IF(ct.transactiontype IN (61, 62, 63, 64), (-1) * ct.amount, 0)
                    )
                ) AS bets")
            ->selectRaw("
                SUM(
                    IF(ct.transactiontype IN (34, 54), 1,
                        IF(ct.transactiontype IN (61, 64), -1, 0)
                    )
                ) AS number_of_wagers")
            ->selectRaw("SUM(IF(ct.transactiontype IN (34, 35, 52, 54, 61, 62, 63, 64), (-1) * ct.amount, 0)) AS wagers_slots")
            ->selectRaw("
                SUM(
                    IF(us.equipment IN ('pc', 'macintosh') AND ct.transactiontype IN (34, 35, 52, 54), (-1) * ct.amount,
                        IF(us.equipment IN ('pc', 'macintosh') AND ct.transactiontype IN (61, 62, 63, 64), (-1) * ct.amount, 0)
                    )
                ) AS wagers_pc")
            ->selectRaw("
                SUM(
                    IF(us.equipment NOT IN ('pc', 'macintosh') AND ct.transactiontype IN (34, 35, 52, 54), (-1) * ct.amount,
                        IF(us.equipment NOT IN ('pc', 'macintosh') AND ct.transactiontype IN (61, 62, 63, 64), (-1) * ct.amount, 0)
                    )
                ) AS wagers_mobile")
            ->leftJoin("users_sessions AS us", "us.id", "=", DB::raw($this->getUserSessionsCashTransactionsSubQuery()))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereNotIn("ct.transactiontype", [38, 85])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "ct.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getDepositsData(): Builder
    {
        return $this->connection
            ->table("deposits AS d")
            ->select("d.user_id")
            ->selectRaw($this->getDateRawQuery("d.timestamp") . " AS gaming_day")
            ->selectRaw("SUM(d.amount) AS deposits")
            ->whereIn("d.user_id", $this->getUsersQuery())
            ->whereNotIn("d.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("d.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "d.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getHighRiskData(): Builder
    {
        return $this->connection
            ->table("risk_profile_rating_log AS r")
            ->select("r.user_id")
            ->selectRaw($this->getDateRawQuery("r.created_at") . " AS gaming_day")
            ->selectRaw("IF(r.rating >= 80, 1, 0) AS high_risk")
            ->whereIn("r.user_id", $this->getUsersQuery())
            ->whereNotIn("r.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("r.created_at")),
                [$this->start_date, $this->end_date])
            ->whereIn("r.id", $this->getRgRiskProfileRatingSubQuery())
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getRgRiskProfileRatingSubQuery(): Builder
    {
        return $this->connection
            ->table("risk_profile_rating_log AS rprl")
            ->selectRaw("max(rprl.id)")
            ->where("rprl.rating_type", "RG")
            ->groupBy(["rprl.user_id", "rprl.rating_type"]);
    }

    /**
     * @return Builder
     */
    private function getUserGameSessionsData(): Builder
    {
        return $this->connection
            ->table("users_game_sessions AS ugs")
            ->select("ugs.user_id")
            ->selectRaw($this->getDateRawQuery("ugs.start_time") . " AS gaming_day")
            ->selectRaw("COUNT(1) AS number_of_game_sessions")
            ->selectRaw("IF(SUM(TIMESTAMPDIFF(MINUTE, ugs.start_time, ugs.end_time)) = 0, 1, SUM(TIMESTAMPDIFF(MINUTE, ugs.start_time, ugs.end_time))) AS total_gameplay_duration")
            ->whereIn("ugs.user_id", $this->getUsersQuery())
            ->whereNotIn("ugs.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("ugs.start_time")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "ugs.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getTournamentGameSessionsData(): Builder
    {
        return $this->connection
            ->table("tournament_entries AS te")
            ->select("te.user_id")
            ->selectRaw($this->getDateRawQuery("t.start_time") . " AS gaming_day")
            ->selectRaw("COUNT(1) AS number_of_game_sessions")
            ->selectRaw("SUM(TIMESTAMPDIFF(MINUTE, t.start_time, t.end_time)) AS total_gameplay_duration")
            ->join("tournaments AS t", "t.id", "=", "te.t_id")
            ->join("bets_mp AS bmp", "bmp.id", '=', DB::raw("(
                SELECT
                    b.id
                FROM
                    bets_mp b
                WHERE
                    te.t_id = b.t_id
                    AND b.created_at BETWEEN t.start_time AND t.end_time
                    AND b.user_id = te.user_id
                LIMIT 1)"))
            ->whereIn("te.user_id", $this->getUsersQuery())
            ->whereNotIn("te.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("t.start_time")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "te.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    private function getActionsBreakStatus(): Builder
    {
        return $this->connection
            ->table("actions AS a")
            ->select("a.target AS user_id")
            ->selectRaw($this->getDateRawQuery("a.created_at") . " AS gaming_day")
            ->selectRaw("IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', 1), '[', -1) IN ('SELF_LOCKED'),
                    DATE_FORMAT(a.created_at - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d %H:%i:%s'),
                    NULL
                ) AS break_end")
            ->selectRaw("IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', -1), ']', 1) IN ('SELF_LOCKED'),
                    DATE_FORMAT(a.created_at - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d %H:%i:%s'),
                    NULL
                ) AS break_start")
            ->selectRaw("IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', 1), '[', -1) IN ('SELF_LOCKED') OR
                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', -1), ']', 1) IN ('SELF_LOCKED'),
                    1,
                    0
                ) AS break_status")
            ->where("a.tag", "user_status_changed")
            ->whereRaw("(
                SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', 1), '[', -1) IN ('SELF_LOCKED') OR
                SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', -1), ']', 1) IN ('SELF_LOCKED')
            )")
            ->whereIn("a.target", $this->getUsersQuery())
            ->whereNotIn("a.target", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("a.created_at")),
                [$this->start_date, $this->end_date]);
    }

    /**
     * @return Builder
     */
    private function getActionsSeStatus(): Builder
    {
        return $this->connection
            ->table("actions AS a")
            ->select("a.target as user_id")
            ->selectRaw($this->getDateRawQuery("a.created_at") . " AS gaming_day")
            ->selectRaw("IF(
                SUBSTRING_INDEX(SUBSTRING_INDEX(a.descr, '-', 1), '[', -1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED'),
                DATE_FORMAT(a.created_at - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d %H:%i:%s'),
                NULL
            ) AS se_end")
            ->selectRaw("IF(
                SUBSTRING_INDEX(SUBSTRING_INDEX(descr, '-', -1), ']', 1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED'),
                DATE_FORMAT(a.created_at - interval " . static::INTERVAL_TIME . " hour, '%Y-%m-%d %H:%i:%s'),
                NULL
            ) AS se_start")
            ->selectRaw("IF(
                SUBSTRING_INDEX(SUBSTRING_INDEX(descr, '-', 1), '[', -1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED') OR
                SUBSTRING_INDEX(SUBSTRING_INDEX(descr, '-', -1), ']', 1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED'),
                1,
                0
            ) AS se_status")
            ->where("a.tag", "user_status_changed")
            ->whereRaw("(
                SUBSTRING_INDEX(SUBSTRING_INDEX(descr, '-', 1), '[', -1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED') OR
                SUBSTRING_INDEX(SUBSTRING_INDEX(descr, '-', -1), ']', 1) IN ('SELF_EXCLUDED', 'EXTERNALLY_SELF_EXCLUDED')
            )")
            ->whereIn("a.target", $this->getUsersQuery())
            ->whereNotIn("a.target", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("a.created_at")),
                [$this->start_date, $this->end_date]);
    }

    /**
     * @return Builder
     */
    private function getWithdrawableWinsData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->select("ct.user_id")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . " AS gaming_day")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NULL THEN ct.amount ELSE 0 END) AS withdrawable_win")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NOT NULL THEN ct.amount ELSE 0 END) AS wins")
            ->leftJoin("users_sessions AS us", "us.id", "=", DB::raw($this->getUserSessionsCashTransactionsSubQuery()))
            ->leftJoin("cash_transactions AS ct2", "ct2.id", "=", DB::raw($this->getWithdrawableWinsSubQuery()))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [38, 85])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["gaming_day", "ct.user_id"])
            ->orderBy("gaming_day");
    }

    /**
     * @return Builder
     */
    protected function getBetRollbacksData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->select("ct.user_id")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . " AS gaming_day")
            ->selectRaw("(b.amount * -1) AS bets")
            ->selectRaw("IF(b.device_type = 0, (b.amount * -1), 0) AS wagers_pc")
            ->selectRaw("IF(b.device_type IN(1,2,3), (b.amount * -1), 0) AS wagers_mobile")
            ->selectRaw("IF(mg.tag IN ('blackjack', 'roulette', 'table'), (b.amount * -1), 0) AS wagers_tables")
            ->selectRaw("IF(mg.tag IN ('casino-playtech', 'other', 'slots', 'slots_tournament', 'slots_jackpot', 'system', 'videoslots','arcade'), (b.amount * -1), 0) AS wagers_slots")
            ->selectRaw("IF(mg.tag IN ('live', 'live-casino'), (b.amount * -1), 0) AS wagers_live_dealer")
            ->selectRaw("IF(mg.tag IN ('videopoker', 'scratch-cards', 'videoslots_jackpot', 'videoslots_jackpotbsg', 'wheel-of-jps'), (b.amount * -1), 0) AS wagers_other")
            ->selectRaw("-1 AS 'number_of_wagers'")
            ->leftJoin("bets AS b", "b.id", "=", "ct.parent_id")
            ->leftJoin("micro_games AS mg", function ($join) {
                $join
                    ->on('b.game_ref', '=', 'mg.ext_game_name')
                    ->on('b.device_type', '=', 'mg.device_type_num');
            })
            ->where("ct.transactiontype", 7)
            ->where("ct.amount", ">", 0)
            ->where(function ($query) {
                $this->getBetsRollbacksDescriptionSubQuery($query);
            })
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->orderBy("gaming_day");
    }

    /**
     * @return void
     */
    private function setData(): void
    {
        $this->data = array_merge(
            $this->bets,
            $this->bonus_entries,
            $this->bet_rollbacks,
            $this->bets_mp,
            $this->cash_transactions,
            $this->user_game_sessions,
            $this->tournament_game_sessions,
            $this->deposits,
            $this->high_risk,
            $this->wins,
            $this->action_break_status,
            $this->action_se_status,
            $this->withdrawable_wins,
        );
    }

    /**
     * @return void
     */
    private function setInitialRow(): void
    {
        $this->initial_row = [
            'currency' => 'CAD',
            'last_active_date' => '1901-01-01',
            'start_balance' => 0,
            'promo_start_balance' => 0,
            'deposits' => 0,
            'withdrawals' => 0,
            'adjustments' => 0,
            'promo_adjustments' => 0,
            'bets' => 0,
            'promo_bets' => 0,
            'wins' => 0,
            'promo_wins' => 0,
            'withdrawable_win' => 0,
            'end_balance' => 0,
            'promo_end_balance' => 0,
            'number_of_game_sessions' => 0,
            'number_of_wagers' => 0,
            'total_gameplay_duration' => 0,
            'wagers_slots' => 0,
            'wagers_tables' => 0,
            'wagers_live_dealer' => 0,
            'wagers_p2p_poker' => 0,
            'wagers_other' => 0,
            'wagers_mobile' => 0,
            'wagers_pc' => 0,
            'break_status' => 0,
            'break_start' => null,
            'break_end' => null,
            'se_status' => 0,
            'se_start' => null,
            'se_end' => null,
            'high_risk' => 0,
        ];
    }

    /**
     * @return void
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            "gaming_day",
            "gaming_site_id",
            "player_id",
            "currency",
            "last_active_date",
            "start_balance",
            "promo_start_balance",
            "deposits",
            "withdrawals",
            "adjustments",
            "promo_adjustments",
            "bets",
            "promo_bets",
            "wins",
            "promo_wins",
            "withdrawable_win",
            "end_balance",
            "promo_end_balance",
            "number_of_game_sessions",
            "number_of_wagers",
            "total_gameplay_duration",
            "wagers_slots",
            "wagers_tables",
            "wagers_live_dealer",
            "wagers_p2p_poker",
            "wagers_other",
            "wagers_mobile",
            "wagers_pc",
            "break_status",
            "break_start",
            "break_end",
            "se_status",
            "se_start",
            "se_end",
            "high_risk",
        ];
    }

    /**
     * @return void
     */
    protected function prepareCsvData(): void
    {
        $this->setInitialRow();
        $this->setData();
        $pa = $this->preparePlayerActivityData();

        foreach ($pa as $gamingDay => $gamingDayArray) {
            foreach ($gamingDayArray as $gamingSite => $gamingSiteArray) {
                foreach ($gamingSiteArray as $user => $userArray) {
                    $this->csv[] = array_merge([$gamingDay, $gamingSite, $user], array_values($userArray));
                }
            }
        }
    }

    /**
     * @return array
     */
    private function preparePlayerActivityData(): array
    {
        $pa = [];

        foreach ($this->data as $row) {
            // set structure if not set
            if (!isset($pa[$row['gaming_day']][$this->gaming_site_id][$row['user_id']])) {
                $pa[$row['gaming_day']][$this->gaming_site_id][$row['user_id']] = $this->initial_row;
            }

            // update columns
            foreach ($row as $columnName => $value) {
                // some columns are not int columns - should be updated in diff way
                if (!in_array($columnName, [
                    'gaming_day',
                    'gaming_site_id',
                    'user_id',
                    'player_id',
                    'game_tag',
                    'se_status',
                    'se_start',
                    'se_end',
                    'break_status',
                    'break_start',
                    'break_end',
                    'high_risk'
                ])) {
                    $pa[$row['gaming_day']][$this->gaming_site_id][$row['user_id']][$columnName] += $value;
                } elseif (in_array($columnName,
                    ['se_status', 'se_start', 'se_end', 'break_status', 'break_start', 'break_end', 'high_risk'])) {
                    $pa[$row['gaming_day']][$this->gaming_site_id][$row['user_id']][$columnName] = $value;
                }
            }
        }

        return $pa;
    }

}
