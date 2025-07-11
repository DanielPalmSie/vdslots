<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportDataGGR extends ExportBaseClass
{
    protected string $report_name = "GGR";

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("export:ggr")
            ->setDescription("Export data for GGR generation in reporting service")
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
                "Name of the CSV to be generated (without `.csv` extension), default convention is `report-name_gaming-site-id_start-date`(ggr_S100062A_2023-11-01)"
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
        $this->setBetRollbacksData();
        $this->setWinsData();
        $this->setBetsDataBOS();
        $this->setWinsDataBOS();
    }

    /**
     * @return void
     */
    protected function setBetsData(): void
    {
        $bets = $this->getBetsData()->get();

        foreach ($bets as $bet) {
            $this->data[$bet['date']]['bets'] += $bet['bet_amount'];
            $this->data[$bet['date']]['wins'] += $bet['win_amount'];
        }
    }

    /**
     * @return void
     */
    protected function setBetRollbacksData(): void
    {
        $betRollbacks = $this->getBetRollbacksData()->get();

        foreach ($betRollbacks as $rollback) {
            $this->data[$rollback['date']]['bets'] -= $rollback['amount'];
        }
    }

    /**
     * @return void
     */
    protected function setWinsData(): void
    {
        $wins = $this->getWinsData()->get();

        foreach ($wins as $win) {
            $this->data[$win['date']]['wins'] += $win['amount'];
            $this->data[$win['date']]['withdrawable_wins'] += $win['withdrawable_win_amount'];
        }

    }

    /**
     * @return void
     */
    private function setBetsDataBOS(): void
    {
        $cashTransactionBets = $this->getBetsBOSData()->get();

        foreach ($cashTransactionBets as $bet) {
            $this->data[$bet['date']]['bets'] += $bet['amount'];
        }
    }

    /**
     * @return void
     */
    private function setWinsDataBOS(): void
    {
        $cashTransactionWins = $this->getWinsBOSData()->get();

        foreach ($cashTransactionWins as $win) {
            $this->data[$win['date']]['withdrawable_wins'] += abs($win['withdrawable_wins']);
            $this->data[$win['date']]['wins'] += abs($win['wins']);
        }
    }

    /**
     * @return Builder
     */
    protected function getBetsData(): Builder
    {
        return $this->connection
            ->table("bets AS b")
            ->selectRaw($this->getDateRawQuery("b.created_at") . "AS date")
            ->selectRaw("SUM(b.amount) AS bet_amount")
            ->selectRaw("SUM(IF(b.bonus_bet = 0, b.jp_contrib, 0)) AS win_amount")
            ->where("b.bonus_bet", 0)
            ->where("b.amount", ">", 0)
            ->whereIn("b.user_id", $this->getUsersQuery())
            ->whereNotIn("b.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("b.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy("date")
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    protected function getBetRollbacksData(): Builder
    {
        return $this->connection
            ->table('cash_transactions AS ct')
            ->selectRaw($this->getDateRawQuery('ct.timestamp') . " AS date")
            ->selectRaw("SUM(ct.amount) AS amount")
            ->where("ct.transactiontype", 7)
            ->where("ct.amount", ">",0)
            ->where(function ($query) {
                $this->getBetsRollbacksDescriptionSubQuery($query);
            })
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy("date");
    }

    /**
     * @return Builder
     */
    protected function getWinsData(): Builder
    {
        return $this->connection
            ->table("wins AS w")
            ->selectRaw("SUM(IF(w.bonus_bet = 0 AND w.award_type != 4, w.amount, 0)) AS amount")
            ->selectRaw("SUM(CASE WHEN w.bonus_bet <> 0 THEN w.amount ELSE 0 END) AS withdrawable_win_amount")
            ->selectRaw($this->getDateRawQuery("w.created_at") . "AS date")
            ->whereIn("w.user_id", $this->getUsersQuery())
            ->whereNotIn("w.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("w.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy("date")
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getBetsBOSData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->selectRaw("SUM(ct.amount * (-1)) AS amount")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . "AS date")
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [34, 35, 52, 54, 61, 62, 63, 64])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy("date")
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getWinsBOSData(): Builder
    {
        return $this->connection
            ->table("cash_transactions AS ct")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NULL THEN ct.amount ELSE 0 END) AS withdrawable_wins")
            ->selectRaw("SUM(CASE WHEN ct2.id IS NOT NULL THEN ct.amount ELSE 0 END) AS wins")
            ->selectRaw($this->getDateRawQuery("ct.timestamp") . "AS date")
            ->leftJoin("cash_transactions AS ct2", "ct2.id", "=", DB::raw($this->getWithdrawableWinsSubQuery()))
            ->whereIn("ct.user_id", $this->getUsersQuery())
            ->whereNotIn("ct.user_id", $this->getTestUsersQuery())
            ->whereIn("ct.transactiontype", [38, 85])
            ->whereBetween(DB::raw($this->getDateRawQuery("ct.timestamp")),
                [$this->start_date, $this->end_date])
            ->groupBy("date")
            ->orderBy("date");
    }

    /**
     * @return void
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            'date',
            'gaming_site_id',
            'product_code',
            'currency',
            'bets',
            'wins',
            'withdrawable_wins'
        ];
    }

    /**
     * @return void
     */
    protected function prepareCsvData(): void
    {
        foreach ($this->data as $date => $dayOfData) {
            $this->csv[] = [
                $date,
                $this->gaming_site_id,
                $this->product_code,
                $this->currency,
                $dayOfData['bets'] ?? 0,
                $dayOfData['wins'] ?? 0,
                $dayOfData['withdrawable_wins'] ?? 0,
            ];
        }

    }
}
