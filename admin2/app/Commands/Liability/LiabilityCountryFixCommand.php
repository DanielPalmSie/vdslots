<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 19/02/18
 * Time: 11:39
 */

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityCountryFixCommand extends LiabilityCommand
{
    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:fix:country")
            ->setDescription("Liability Fix Country: setup country column in users_monthly_liability.")
            ->addArgument('year', InputArgument::OPTIONAL, 'Year')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month')
            ->addArgument('chunk_size', InputArgument::OPTIONAL,
                'Number of updates at once', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $year = $input->getArgument('year');
        $month = $input->getArgument('month');
        $chunk_size = $input->getArgument('chunk_size');

        if (!empty($year) && !empty($month)) {
            $this->prepareAndFix($year, $month, $output, $chunk_size);
        } elseif (empty($month) && !empty($year)) {
            for ($i = 1; $i <= 12; $i++) {
                $output->writeln("Processing year: {$year}, month: {$i}");
                $this->prepareAndFix($year, $i, $output, $chunk_size);
            }
        } else {
            //get first year from users_monthly_liability
            $min_year = DB::shsSelect("users_monthly_liability",
                "SELECT min(year) AS year FROM users_monthly_liability");
            $min_year = collect($min_year)->sortBy('year')->first()->year;

            for ($year = (int)$min_year; $year <= (int)date('Y'); $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    $output->writeln("Processing year: {$year}, month: {$month}");

                    $this->prepareAndFix($year, $month, $output, $chunk_size);

                    $output->writeln("Done");
                }
            }
        }

        return 0;
    }

    /**
     * @param                 $year
     * @param                 $month
     * @param OutputInterface $output
     * @param                 $chunk_size
     */
    private function prepareAndFix($year, $month, $output, $chunk_size)
    {
        $month = count($month) > 1 ? $month
            : str_pad($month, 2, '0', STR_PAD_LEFT);
        $progress = 0;
        $total = 0;
        $sql = "SELECT country, user_id 
            FROM users_daily_stats 
            WHERE date LIKE '{$year}-{$month}-%' 
            GROUP BY user_id";

        collect(DB::shsSelect('users_daily_stats', $sql))
            ->tap(function ($data) use ($chunk_size, &$total) {
                /** @var Collection $data */
                $total = intval($data->count() / $chunk_size) + 1;
            })
            ->chunk($chunk_size)
            ->each(function ($chunk) use (
                $year,
                $month,
                $output,
                &$progress,
                $total
            ) {
                $progress += 1;
                $output->writeln("Progress: {$progress}/{$total}");
                $this->fixCountry($year, $month, $chunk);
            });

        //Fixing here the users missed on the script above as for some reason some PLR don't have UDS row
        $missing = DB::connection()->select("SELECT * FROM users_monthly_liability uml WHERE source = 0 
                                            AND year = {$year} AND month = {$month} AND country = ''");

        foreach ($missing as $row) {
            $uds = DB::connection()->table('users_daily_stats')->select('country')
                ->where('user_id', $row->user_id)->where('country', '!=', '')->first();

            DB::connection()->statement("UPDATE users_monthly_liability SET country = '{$uds->country}' 
                                                 WHERE source = 0 AND year = {$year} AND month = {$month} 
                                                  AND country = '' AND user_id = '{$row->user_id}'");
        }
    }

    /**
     * @param                                $year
     * @param                                $month
     * @param \Illuminate\Support\Collection $users_country
     */
    private function fixCountry($year, $month, $users_country)
    {
        $conditional_assign = function ($el) {
            return "WHEN user_id = {$el->user_id} THEN '{$el->country}' ";
        };
        $update = " UPDATE users_monthly_liability 
            SET country = CASE 
              {$users_country
                ->map($conditional_assign)
                ->implode(" ")}
            END 
            WHERE user_id IN (
                {$users_country
                    ->pluck('user_id')
                    ->implode(', ')}
            ) AND source = 0 AND year = {$year} AND month = {$month} ";

        DB::shsStatement('users_monthly_liability', $update);

        DB::connection()->statement($update);
    }
}
