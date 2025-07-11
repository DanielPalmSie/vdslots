<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportDataJpWins extends ExportBaseClass
{
    protected string $report_name = "jp_wins";

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("export:jp-wins")
            ->setDescription("Export data Jackpot wins")
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
        $this->setWinsData();
    }

    /**
     * @return void
     */
    protected function setWinsData(): void
    {
        $wins = $this->getWinsData()->get();

        foreach ($wins as $win) {
            $this->data[$win['date']]['wins'] -= $win['amount'];
        }
    }

    /**
     * @return Builder
     */
    protected function getWinsData(): Builder
    {
        return $this->connection
            ->table("wins AS w")
            ->selectRaw($this->getDateRawQuery("w.created_at") . " AS date")
            ->selectRaw("SUM(IF(bonus_bet = 0 AND award_type = 4, amount, 0)) as amount")
            ->whereIn("w.user_id", $this->getUsersQuery())
            ->whereNotIn("w.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("w.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date"])
            ->orderBy("date");
    }

    /**
     * @return Builder
     */
    private function getBetsBOSData(): Builder
    {
        return $this->connection;
    }

    /**
     * @return Builder
     */
    private function getWinsBOSData(): Builder
    {
        return $this->connection;
    }

    /**
     * @return void
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            'date',
            'wins',
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
                $dayOfData['wins'] ? $dayOfData['wins'] / 100 : 0,
            ];
        }

    }
}
