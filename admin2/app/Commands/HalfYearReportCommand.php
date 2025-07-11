<?php

namespace App\Commands;

use App\Models\RegulatoryStats;
use App\Repositories\HalfYearReportRepository;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Extensions\Database\FManager as DB;

class HalfYearReportCommand extends Command
{
    /** @var Application $app */
    private $app;
    /** @var HalfYearReportRepository $app */
    private $repo;
    /** @var string $jurisdiction */
    private string $jurisdiction;

    public function __construct()
    {
        parent::__construct();

        // Conditions were inverted.. we are in july at was creating last year instead of current.
        $is_first_half_year = Carbon::now()->lessThan(Carbon::now()->startOfYear()->addMonths(5)->endOfMonth());

        if ($is_first_half_year) {
            // get the last half of the last year
            $last_year = Carbon::now()->subYear(1)->startOfYear();
            $start_date = $last_year->copy()->addMonths(6)->startOfMonth();
            $end_date = $last_year->copy()->lastOfYear()->endOfDay();
        } else {
            //get the first half of the current year
            $current_year = Carbon::now()->startOfYear();
            $start_date = $current_year->copy()->startOfYear();
            $end_date = $current_year->copy()->addMonths(5)->endOfMonth();
        }

        $this->repo = new HalfYearReportRepository();
        $this->repo->setInterval($start_date, $end_date);
    }

    /**
     * Initialize the mailer.
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName("half-year")
            ->setDescription("Calculate half year reports.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getSilexApplication();
        $this->repo->setApp($this->app);

        $this->handleCountry('SE', 'getSEReport', 'Casino');
        $this->__construct();
        $this->handleCountry('SE', 'getSEReport', 'Sport');

        return 0;
    }

    /**
     * @param $country
     * @param $method
     * @param $product
     */
    private function handleCountry($country, $method, $product)
    {
        $this->repo->setCountry($country);
        $this->repo->setProduct($product);

        $this->jurisdiction = $country . "_" . $product;

        try {
            $already_generated = DB::table('regulatory_stats')
                    ->where('start_date', '=', $s = $this->repo->getStartDate())
                    ->where('end_date', '=', $e = $this->repo->getEndDate())
                    ->where('jurisdiction', '=', $this->jurisdiction)
                    ->get()
                    ->count() > 0;

            if ($already_generated) {
                $this->app['monolog']->addError("regulatory-stats: Tried to regenerate the stats for start=$s end=$e]");
                return;
            }
            DB::bulkInsert('regulatory_stats', null, $this->{$method}());
        } catch (\Exception $e) {
            $this->app['monolog']->addError("regulatory-stats: {$e->getMessage()}");
        }
    }

    /**
     * @param $product
     * @return array
     */
    private function getSEReport()
    {
        echo "Exporting raw data for Finance related questions." . "\n";
        $this->repo->NGRPerPlayerReport();

        // [category, sub_category, value, type]
        $rows = [];
        $question_title = '1. Number of registered players';
        echo  $question_title . "\n";
        $this->addGroupedByGenderAge($question_title, $this->repo->numberOfRegisteredPlayers(), $rows, RegulatoryStats::TYPE_UNITS);
        $question_title = '2. Number of players who have placed bets';
        echo  $question_title . "\n";
        $this->addGroupedByGenderAge($question_title, $this->repo->numberOfUsersWhoPlacedBets(), $rows, RegulatoryStats::TYPE_UNITS);
        $question_title = '3. Number of players who have respectively raised or lowered their limit in time or money respectively';
        echo  $question_title . "\n";
        $this->addWithSubcategories($question_title, $this->repo->playersWhoRaisedLoweredLimits(), $rows, ['lowered time' => RegulatoryStats::TYPE_UNITS, 'raised time' => RegulatoryStats::TYPE_UNITS, 'lowered money' => RegulatoryStats::TYPE_UNITS, 'raised money' => RegulatoryStats::TYPE_UNITS]);
        $question_title = '4. Number of players who has reached their limit in time or money respectively';
        echo  $question_title . "\n";
        $this->addWithSubcategories($question_title, $this->repo->playersWhoReachedLimit2(), $rows, ['time' => RegulatoryStats::TYPE_UNITS, 'money' => RegulatoryStats::TYPE_MONEY]);
        $question_title = '5. Number of players who completed the self-assessment test';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->playersWhoCompletedSelfAssessment(), RegulatoryStats::TYPE_UNITS];
        $question_title = '6. Number of persons who has contacted the license holder about gambling issues';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->contactedRegardingGamblingIssue(), RegulatoryStats::TYPE_UNITS];
        $question_title = '7. Number of accounts closed by the license holder or the player respectively';
        echo  $question_title . "\n";
        $this->addWithSubcategories($question_title, $this->repo->numberOfClosedAccounts(), $rows, ['terminated by player' => RegulatoryStats::TYPE_UNITS, 'terminated by licensee' => RegulatoryStats::TYPE_UNITS]);
        $question_title = '8. Number of excluded accounts for 24h, certain time period or continuously respectively';
        echo  $question_title . "\n";
        $this->addGroupedByGenderAge($question_title, $this->repo->excludedAccounts(), $rows, RegulatoryStats::TYPE_UNITS);
        $question_title = '9. Number of players who a has been contacted by license holder by suspected or confirmed gambling issues';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->suspectedOrConfirmedGamblingIssue(), RegulatoryStats::TYPE_UNITS];
        $question_title = '10. Number of the contacted players according to point 9 who reduced gambling and how much the gambling was reduced in average in percent';
        echo  $question_title . "\n";
        $this->addWithSubcategories($question_title, $this->repo->gamblingIssueReduced(), $rows, ['number of players who reduced gambling' => RegulatoryStats::TYPE_UNITS, 'how much the gambling was reduced in average in percent' => RegulatoryStats::TYPE_PERCENT]);
        $question_title = '11. Number of the contacted players according to point 9 who chose to exclude themselves from gambling';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->gamblingIssueSelfExcluded(), RegulatoryStats::TYPE_UNITS];
        $question_title = '12. Share of the total net sales which is from the top 5% ranked players according to net sales';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->sharesFromTop5Percent(), RegulatoryStats::TYPE_PERCENT];
        $question_title = '13. Net sales, in average and median, for players according to point 12';
        echo  $question_title . "\n";
        $this->addWithSubcategories($question_title, $this->repo->netSalesAverageMedian(), $rows, RegulatoryStats::TYPE_MONEY);
        $question_title = '14. Share of point 12 players who has been contacted by license holder';
        echo  $question_title . "\n";
        $rows[] = [$question_title, '', $this->repo->topPlayersContacted(), RegulatoryStats::TYPE_PERCENT];

        return array_map(function ($row) {
            return [
                'jurisdiction' => $this->jurisdiction,
                'category' => $row[0],
                'subcategory' => $row[1],
                'value' => $row[2],
                'type' => $row[3],
                'start_date' => $this->repo->getStartDate(),
                'end_date' => $this->repo->getEndDate()
            ];
        }, $rows);
    }

    /**
     * Method used to populate the list of rows with items that follow this structure:
     *  $row = [category, sub_category, value, type]
     *
     * @param $title
     * @param $data
     * @param $target_array
     * @param $type
     */
    private function addGroupedByGenderAge($title, $data, &$target_array, $type)
    {
        foreach ($data as $age_group => $users) {
            foreach ($users as $gender_group => $count) {
                if (is_array($count)) {
                    // expected $count is array like: [key1 => number, key2 => number]
                    foreach ($count as $key => $c) {
                        $target_array[] = [$title, "$age_group, $gender_group, $key", $c, $type];
                    }
                } else {
                    $target_array[] = [$title, "$age_group, $gender_group", $count, $type];
                }
            }
        }
    }

    /**
     * @param $title
     * @param $data
     * @param $target_array
     * @param $type
     */
    private function addWithSubcategories($title, $data, &$target_array, $type)
    {
        foreach ($data as $subcategory => $value) {
            $final_type = $type;
            // if a subcategory have a different type we set it here.
            if(is_array($type) && !empty($type[$subcategory])) {
                $final_type = $type[$subcategory];
            }
            $target_array[] = [$title, $subcategory, $value, $final_type];
        }
    }

}
