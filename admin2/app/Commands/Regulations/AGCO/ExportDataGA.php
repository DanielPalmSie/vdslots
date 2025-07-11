<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportDataGA extends ExportBaseClass
{
    protected string $report_name = 'GA';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("export:ga")
            ->setDescription('Export data for GA generation in reporting service')
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
                "Full file path where the CSV should stored",
                getenv('STORAGE_PATH') . "/" . static::STORAGE_PATH . "/" . $this->report_name
            )
            ->addArgument(
                "file_name",
                InputArgument::OPTIONAL,
                "Name of the CSV to be generated (without `.csv` extension), default convention is `report-name_gaming-site-id_start-date`(ga_S100062A_2023-11-01)"
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
        $this->setFreeSpinBetsData();
        $this->setBetsDataBOS();
        $this->setCashTransactionBetsData();
        $this->setFreeRollBetsBOSData();
        $this->setWinsData();
        $this->setWinsDataBOS();
        $this->setUserGameSessionsData();
        $this->setUserGameSessionsUniquePlayersData();
        $this->setFreeSpinsUniquePlayersData();
        $this->setTournamentGameSessionData();
        $this->setBetRollbacksData();
    }

    /**
     * @return void
     */
    protected function setBetsData(): void
    {
        $bets = $this->getBetsData()->get();

        foreach ($bets as $bet) {
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_amount'] += $bet['bet_amount'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['promo_bet_amount'] += $bet['promo_bet_amount'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_count'] += $bet['bet_count'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['win_amount'] += $bet['win_amount'];
        }
    }

    /**
     * @return void
     */
    private function setFreeSpinBetsData(): void
    {
        $betsFS = $this->getFreeSpinBetsData()->get();

        foreach ($betsFS as $bet) {
            $this->data[$bet['date']][$bet['ext_game_name']][$bet['device_type']]['promo_bet_amount'] += $bet['promo_bet_amount'];
            $this->data[$bet['date']][$bet['ext_game_name']][$bet['device_type']]['bet_count'] += $bet['promo_bet_count'];
        }
    }

    /**
     * @return void
     */
    private function setBetsDataBOS(): void
    {
        $betsBOS = $this->getBetsDataBOS()->get();

        foreach ($betsBOS as $bet) {
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_amount'] += $bet['amount'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['fees'] += $bet['fees'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_count'] += $bet['bet_count'];
        }
    }

    /**
     * @return void
     */
    private function setCashTransactionBetsData(): void
    {
        $cashTransactionBets = $this->getCashTransactionBetsData()->get();

        foreach ($cashTransactionBets as $bet) {
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_amount'] += $bet['amount'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['fees'] += $bet['fees'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_count'] += $bet['bet_count'];
        }
    }

    /**
     * @return void
     */
    private function setFreeRollBetsBOSData(): void
    {
        $freeRollBets = $this->getFreeRollBetsBOSData()->get();

        foreach ($freeRollBets as $bet) {
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['promo_bet_amount'] += $bet['promo_bet_amount'];
            $this->data[$bet['date']][$bet['game_ref']][$bet['device_type']]['bet_count'] += $bet['promo_bet_count'];
        }
    }

    /**
     * @return void
     */
    protected function setWinsData(): void
    {
        $wins = $this->getWinsData()->get();

        foreach ($wins as $win) {
            $this->data[$win['date']][$win['game_ref']][$win['device_type']]['win_amount'] += $win['amount'];
            $this->data[$win['date']][$win['game_ref']][$win['device_type']]['withdrawable_win_amount'] += $win['withdrawable_win_amount'];
        }

    }

    /**
     * @return void
     */
    private function setWinsDataBOS(): void
    {
        $winsBOS = $this->getWinsDataBOS()->get();

        foreach ($winsBOS as $win) {
            $this->data[$win['date']][$win['game_ref']][$win['device_type']]['withdrawable_win_amount'] += abs($win['withdrawable_win_amount']);
            $this->data[$win['date']][$win['game_ref']][$win['device_type']]['win_amount'] += abs($win['win_amount']);
        }
    }

    /**
     * @return void
     */
    private function setUserGameSessionsData(): void
    {
        $userGameSessions = $this->getUserGameSessionsData()->get();

        foreach ($userGameSessions as $ugs) {
            $this->data[$ugs['date']][$ugs['game_ref']][$ugs['device_type_num']]['minutes_played'] += $ugs['minutes_played'] ?: 1;
            $this->data[$ugs['date']][$ugs['game_ref']][$ugs['device_type_num']]['session_count'] += $ugs['session_count'];
        }
    }

    /**
     * @return void
     */
    private function setUserGameSessionsUniquePlayersData(): void
    {
        $uniquePlayers = $this->getUserGameSessionsUniquePlayersData()->get();

        foreach ($uniquePlayers as $uniquePlayer) {
            $this->data[$uniquePlayer['date']][$uniquePlayer['game_ref']][$uniquePlayer['device_type_num']]['unique_players'][$uniquePlayer['user_id']] = true;
        }
    }

    /**
     * @return void
     */
    private function setFreeSpinsUniquePlayersData(): void
    {
        $fsUniquePlayers = $this->getFreeSpinsUniquePlayersData()->get();

        foreach ($fsUniquePlayers as $fsUniquePlayer) {
            $this->data[$fsUniquePlayer['date']][$fsUniquePlayer['ext_game_name']][$fsUniquePlayer['device_type']]['unique_players'][$fsUniquePlayer['user_id']] = true;
        }
    }

    /**
     * @return void
     */
    private function setTournamentGameSessionData(): void
    {
        $tournamentUniquePlayers = $this->getTournamentGameSessionData()->get();

        foreach ($tournamentUniquePlayers as $tournamentUniquePlayer) {
            $this->data[$tournamentUniquePlayer['date']][$tournamentUniquePlayer['game_ref']][$tournamentUniquePlayer['device_type']]['minutes_played'] += $tournamentUniquePlayer['minutes_played']?:1;
            $this->data[$tournamentUniquePlayer['date']][$tournamentUniquePlayer['game_ref']][$tournamentUniquePlayer['device_type']]['session_count'] += $tournamentUniquePlayer['session_count'];
            foreach ($this->data[$tournamentUniquePlayer['date']][$tournamentUniquePlayer['game_ref']][$tournamentUniquePlayer['device_type']]['users'] as $user) {
                $this->data[$tournamentUniquePlayer['date']][$tournamentUniquePlayer['game_ref']][$tournamentUniquePlayer['device_type']]['unique_players'][$user] = true;
            }
        }
    }

    /**
     * @return void
     */
    protected function setBetRollbacksData(): void
    {
        $betRollbacks = $this->getBetRollbacksData()->get();

        foreach ($betRollbacks as $rollback) {
            $this->data[$rollback['date']][$rollback['game_ref']][$rollback['device_type']]['bet_amount'] -= $rollback['amount'];
            $this->data[$rollback['date']][$rollback['game_ref']][$rollback['device_type']]['bet_count'] -= $rollback['bet_count'];
        }
    }

    /**
     * @return Builder
     */
    private function getFreeSpinsUniquePlayersUserGameSessionsSubQuery(): Builder
    {
        return $this->connection
            ->table("users_game_sessions AS ugs")
            ->select(["ugs.session_id"])
            ->whereBetween("be.last_change", ["ugs.start_time", "ugs.end_time"])
            ->whereNotIn("ugs.id", $this->getZeroedUsersGameSessions())
            ->where("mg.ext_game_name", "ugs.game_ref")
            ->where("mg.ext_game_name", "ugs.device_type_num");
    }

    /**
     * @return Builder
     */
    protected function getBetsData(): Builder
    {
        return $this->connection
            ->table("bets AS b")
            ->select(["b.game_ref", "b.device_type"])
            ->selectRaw("SUM(IF (b.bonus_bet = 0, b.amount, 0)) AS bet_amount")
            ->selectRaw("SUM(IF (b.bonus_bet != 0 AND b.bonus_bet != 3, b.amount, 0)) AS promo_bet_amount")
            ->selectRaw($this->getDateRawQuery("b.created_at") . " AS date")
            ->selectRaw("SUM(IF (b.bonus_bet != 3, 1, 0)) AS bet_count")
            ->selectRaw("SUM(IF (b.bonus_bet = 0, b.jp_contrib, 0)) AS win_amount")
            ->where("b.amount", ">", "0")
            ->whereIn("b.user_id", $this->getUsersQuery())
            ->whereNotIn("b.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("b.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "game_ref", "device_type"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getFreeSpinBetsData(): Builder
    {
        return $this->connection
            ->table("bonus_entries AS be")
            ->select(["mg.ext_game_name"])
            ->selectRaw("SUM(bt.frb_cost) AS promo_bet_amount")
            ->selectRaw($this->getDateRawQuery("be.last_change")." AS date")
            ->selectRaw("SUM(bt.reward) AS promo_bet_count")
            ->selectRaw($this->getDeviceTypeRawQuery()." AS device_type")
            ->join("bonus_types AS bt", "be.bonus_id", "=", "bt.id")
            ->leftJoin("users_sessions AS us", "us.id", "=", DB::raw($this->getUserSessionsBonusEntriesSubQuery()))
            ->join("micro_games AS mg", "mg.id", "=", DB::raw($this->getMicroGamesSubQuery()))
            ->whereIn("be.user_id", $this->getUsersQuery())
            ->whereNotIn("be.user_id", $this->getTestUsersQuery())
            ->where("bt.bonus_type", "freespin")
            ->where("be.status", "approved")
            ->whereBetween(DB::raw($this->getDateRawQuery("be.last_change")),
                [$this->start_date, $this->end_date])
            ->groupBy([
                "date",
                "mg.ext_game_name",
                DB::raw($this->getDeviceTypeRawQuery())
            ])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getBetsDataBOS(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->selectRaw("0 AS device_type")
            ->selectRaw("SUM(ct.amount * (-1)) AS amount")
            ->selectRaw("SUM(IF(ct.transactiontype = 52, ct.amount*-1, 0)) AS fees")
            ->selectRaw("SUM(if(ct.transactiontype in (34,54), 1, 0)) AS bet_count")
            ->selectRaw($this->getDateRawQuery("ct.timestamp")." AS date")
            ->selectRaw("CONCAT(t.game_ref, '_tournament') AS game_ref")
            ->leftJoin("tournaments AS t", "t.id", "=", DB::raw("substr(replace(ct.description,'-cancelled','') , 5)"))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [34, 35, 52, 54])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "t.game_ref"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getCashTransactionBetsData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->selectRaw("SUM(ct.amount * (-1)) AS amount")
            ->selectRaw("SUM(IF(ct.transactiontype = 63, ct.amount*-1, 0)) AS fees")
            ->selectRaw("SUM(if(ct.transactiontype in (61,64), -1, 0)) AS bet_count")
            ->selectRaw($this->getDateRawQuery("ct.timestamp")." AS date")
            ->selectRaw("CONCAT(t.game_ref, '_tournament') AS game_ref")
            ->selectRaw("0 AS device_type")
            ->join("cash_transactions AS ct2", "ct2.id", '=', DB::raw("replace(ct.description,'-cancelled','')"))
            ->leftJoin("tournaments AS t", "t.id", "=", DB::raw("substr(replace(ct2.description,'-cancelled','') , 5)"))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [61, 62, 63, 64])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "t.game_ref"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getFreeRollBetsBOSData(): Builder
    {
        return $this->connection
            ->table("bets_mp AS bmp")
            ->select(["bmp.t_id"])
            ->selectRaw("SUM(bmp.amount) AS promo_bet_amount")
            ->selectRaw($this->getDateRawQuery("bmp.created_at")." AS date")
            ->selectRaw("CONCAT(bmp.game_ref, '_tournament') AS game_ref")
            ->selectRaw("0 AS device_type")
            ->selectRaw("COUNT(*) AS promo_bet_count")
            ->join("tournaments AS t", "t.id", "=", "bmp.t_id")
            ->whereIn("bmp.user_id", $this->getUsersQuery())
            ->whereNotIn("bmp.user_id", $this->getTestUsersQuery())
            ->where("t.category", "freeroll")
            ->where("bmp.bonus_bet", "0")
            ->where("bmp.amount", ">", "0")
            ->whereBetween(DB::raw($this->getDateRawQuery("bmp.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "bmp.game_ref", "bmp.t_id"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    protected function getWinsData(): Builder
    {
        return $this->connection
            ->table("wins AS w")
            ->select(["w.game_ref", "w.device_type"])
            ->selectRaw("SUM(IF(w.bonus_bet = 0 AND w.award_type != 4, w.amount, 0)) AS amount")
            ->selectRaw("SUM(CASE WHEN w.bonus_bet <> 0 THEN w.amount ELSE 0 END) AS withdrawable_win_amount")
            ->selectRaw($this->getDateRawQuery("w.created_at") . " AS date")
            ->whereIn("w.user_id", $this->getUsersQuery())
            ->whereNotIn("w.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("w.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "w.game_ref", "w.device_type"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getWinsDataBOS(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NULL THEN ct.amount ELSE 0 END) AS withdrawable_win_amount")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NOT NULL THEN ct.amount ELSE 0 END) AS win_amount")
            ->selectRaw($this->getDateRawQuery("ct.timestamp")." AS date")
            ->selectRaw("CONCAT(t.game_ref, '_tournament') AS game_ref")
            ->selectRaw("0 AS device_type")
            ->leftJoin("tournaments AS t", "t.id", "=",
                DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(ct.description, '-', 2), '-', -1)"))
            ->leftJoin("cash_transactions AS ct2", "ct2.id", "=", DB::raw($this->getWithdrawableWinsSubQuery()))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [38, 85])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "t.game_ref"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getUserGameSessionsData(): Builder
    {
        return $this->connection
            ->table("users_game_sessions AS ugs")
            ->select(["ugs.game_ref", "ugs.device_type_num"])
            ->selectRaw("SUM(TIMESTAMPDIFF(MINUTE, ugs.start_time, ugs.end_time)) AS minutes_played")
            ->selectRaw($this->getDateRawQuery("ugs.start_time") . " AS date")
            ->selectRaw("COUNT(*) AS session_count")
            ->whereIn("ugs.user_id", $this->getUsersQuery())
            ->whereNotIn("ugs.user_id", $this->getTestUsersQuery())
            ->whereNotIn("ugs.id", $this->getZeroedUsersGameSessions())
            ->whereBetween(DB::raw($this->getDateRawQuery("ugs.start_time")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "ugs.device_type_num", "ugs.game_ref"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getUserGameSessionsUniquePlayersData(): Builder
    {
        return $this->connection
            ->table("users_game_sessions AS ugs")
            ->select(["ugs.user_id", "ugs.game_ref", "ugs.device_type_num"])
            ->selectRaw($this->getDateRawQuery("ugs.start_time") . " AS date")
            ->whereIn("ugs.user_id", $this->getUsersQuery())
            ->whereNotIn("ugs.user_id", $this->getTestUsersQuery())
            ->whereNotIn("ugs.id", $this->getZeroedUsersGameSessions())
            ->whereBetween(DB::raw($this->getDateRawQuery("ugs.start_time")),
                [$this->start_date, $this->end_date])
            ->groupBy(["ugs.user_id", "ugs.game_ref", "date", "ugs.device_type_num"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getFreeSpinsUniquePlayersData(): Builder
    {
        return $this->connection
            ->table("bonus_entries AS be")
            ->select(["be.user_id", "mg.ext_game_name"])
            ->selectRaw($this->getDateRawQuery("be.last_change")." AS date")
            ->selectRaw($this->getDeviceTypeRawQuery()." AS device_type")
            ->join("bonus_types AS bt", "bt.id", "=", "be.bonus_id")
            ->leftJoin("users_sessions AS us", "us.id", "=", DB::raw($this->getUserSessionsBonusEntriesSubQuery()))
            ->join("micro_games AS mg", "mg.id", "=", DB::raw($this->getMicroGamesSubQuery()))
            ->whereIn("be.user_id", $this->getUsersQuery())
            ->whereNotIn("be.user_id", $this->getTestUsersQuery())
            ->whereNotIn("us.id", $this->getFreeSpinsUniquePlayersUserGameSessionsSubQuery())
            ->where("bt.bonus_type", "freespin")
            ->where("be.status", "approved")
            ->whereBetween(DB::raw($this->getDateRawQuery("be.last_change")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "mg.ext_game_name", "device_type"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getTournamentGameSessionData(): Builder
    {
        return $this->connection
                ->table("tournament_entries AS te")
                ->selectRaw("SUM(TIMESTAMPDIFF(MINUTE, t.start_time, t.end_time)) AS minutes_played")
                ->selectRaw("CONCAT(t.game_ref, '_tournament') AS game_ref")
                ->selectRaw("0 AS device_type")
                ->selectRaw($this->getDateRawQuery("t.start_time")." AS date")
                ->selectRaw("COUNT(te.id) AS session_count")
                ->selectRaw("GROUP_CONCAT(distinct te.user_id) AS users")
                ->join("tournaments AS t", "t.id", "=", "te.t_id")
                ->join("bets_mp AS bmp", "bmp.id", "=", DB::raw("(
                    SELECT
                        b.id
                    FROM
                        bets_mp b
                    WHERE
                        te.t_id = b.t_id
                        AND b.created_at BETWEEN t.start_time AND t.end_time
                        AND b.user_id = te.user_id
                    LIMIT 1
                    )"))
                ->whereIn("te.user_id", $this->getUsersQuery())
                ->whereNotIn("te.user_id", $this->getTestUsersQuery())
                ->whereBetween(DB::raw($this->getDateRawQuery("t.start_time")),
                    [$this->start_date, $this->end_date])
                ->groupBy(["date", "t.game_ref"])
                ->orderBy("date");
    }

    /**
     * @return Builder
     */
    protected function getBetRollbacksData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->select(["b.game_ref", "b.device_type"])
            ->selectRaw("SUM(ct.amount) AS amount")
            ->selectRaw("COUNT(1) AS bet_count")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . " AS date")
            ->join("bets AS b", "b.id", "=", "ct.parent_id")
            ->where("ct.transactiontype", 7)
            ->where("ct.amount", ">", 0)
            ->where(function ($query) {
                $this->getBetsRollbacksDescriptionSubQuery($query);
            })
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date", "b.game_ref", "b.device_type"]);
    }

    /**
     * @return void
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            'date',
            'game_ref',
            'device_type',
            'brand',
            'currency',
            'bet_amount',
            'promo_bet_amount',
            'win_amount',
            'withdrawable_win_amount',
            'fees',
            'bet_count',
            'session_count',
            'minutes_played',
            'unique_players'
        ];
    }

    /**
     * @return void
     */
    protected function prepareCsvData(): void
    {
        foreach ($this->data as $date => $dayOfData) {
            foreach ($dayOfData as $gameRef => $perDeviceTypeData) {
                foreach ($perDeviceTypeData as $deviceType => $dataRow) {
                    $this->csv[] = [
                        $date,
                        $gameRef,
                        $deviceType,
                        $this->brand,
                        $this->currency,
                        $dataRow['bet_amount'] ?? 0,
                        $dataRow['promo_bet_amount'] ?? 0,
                        $dataRow['win_amount'] ?? 0,
                        $dataRow['withdrawable_win_amount'] ?? 0,
                        $dataRow['fees'] ?? 0,
                        $dataRow['bet_count'] ?? 0,
                        $dataRow['session_count'] ?? 0,
                        $dataRow['minutes_played'] ?? 0,
                        json_encode($dataRow['unique_players'] ?? ''),
                    ];
                }
            }
        }
    }
}
