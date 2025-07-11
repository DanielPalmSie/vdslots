<?php

namespace App\Commands\Regulations\AGCO;

use App\Extensions\Database\Builder;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExportDataJpContrib extends ExportBaseClass
{
    protected string $report_name = "jp_contrib";

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("export:jp-contrib")
            ->setDescription("Export data for Jackpot contribution")
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
    }

    /**
     * @return void
     */
    protected function setBetsData(): void
    {
        $bets = $this->getBetsData()->get();

        foreach ($bets as $bet) {
            $this->data[$bet['date']]['wins'] += $bet['win_amount'];
        }
    }

    /**
     * @return Builder
     */
    protected function getBetsData(): Builder
    {
        return $this->connection
            ->table("bets AS b")
            ->selectRaw($this->getDateRawQuery("b.created_at") . " AS date")
            ->selectRaw("SUM(IF (b.bonus_bet = 0, b.jp_contrib, 0)) AS win_amount")
            ->where("b.amount", ">", "0")
            ->whereIn("b.user_id", $this->getUsersQuery())
            ->whereNotIn("b.user_id", $this->getTestUsersQuery())
            ->whereBetween(DB::raw($this->getDateRawQuery("b.created_at")),
                [$this->start_date, $this->end_date])
            ->groupBy(["date"])
            ->orderBy("date");
    }

    /**
     * @return void
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            'date',
            'contrib',
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
