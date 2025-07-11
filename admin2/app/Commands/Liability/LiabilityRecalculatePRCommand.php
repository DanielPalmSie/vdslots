<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2/1/17
 * Time: 12:28 PM
 */

namespace App\Commands\Liability;

use App\Classes\FormBuilder\Collection;
use App\Classes\PR;
use App\Commands\LiabilityCommand;
use App\Models\UserDailyBalance;
use App\Models\UserMonthlyLiability;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class LiabilityRecalculatePRCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    /** @var  InputInterface $input */
    protected $input;

    protected function configure()
    {
        $this->setName("liability:recalculate:pr")
            ->setDescription("Liability Job: recalculate partnerroom data given a year and a month.")
            ->addArgument('action', InputArgument::OPTIONAL, 'Action', 'recalc')
            ->addArgument('year', InputArgument::OPTIONAL, 'Year')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        if ($input->getArgument('action') == 'rebuild') {
            return $this->rebuildBalances();
        }

        $year = (int)$input->getArgument('year');
        $month = (int)$input->getArgument('month');

        if ($input->getArgument('action') != 'recalc' && (empty($year) || empty($month))) {
            $this->output->writeln("Action is not valid or year/month is null.");
            return 1;
        }

        $date = Carbon::create($year, $month, 1, 0, 0, 0);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Are you sure you want to recalculate $year-$month?", false);

        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        if ($date->lt(Carbon::create(2016, 11, 1, 0, 0, 0))) {
            $this->output->writeln("{$this->now()} Partnerroom data can only be recalculated after Nov 2016 (inclusive).");
            return 1;
        }

        $this->output->writeln("Deleting previous data");
        DB::connection()->table('users_monthly_liability')->where('source', 1)->where('year', $year)->where('month', $month)->delete();
        $this->output->writeln("{$this->now()} Previously inserted data deleted.");

        $repo = new LiabilityRepository($year, $month);

        $this->generatePartnerroomData($repo); //todo shard review

        $this->output->writeln("{$this->now()} Finished.");

        return 0;
    }

    private function rebuildBalances()
    {
        $helper = $this->getHelper('question');
        $question = new Question('Select the day to start [Default: exit]', 'exit');

        $q_response = $helper->ask($this->input, $this->output, $question);

        if ($q_response == 'exit') {
            return 1;
        }

        if (Carbon::createFromFormat('Y-m-d', $q_response) === false) {
            $this->output->writeln("Date format is not valid, input provided: $q_response");
            return 1;
        }

        $date = Carbon::parse($q_response);

        if ($date->lessThan(Carbon::now()->subMonth()->startOfMonth())) {
            $this->output->writeln("Not possible to rebuild previous months, input provided: $q_response");
            return 1;
        } elseif ($date->isFuture()) {
            $this->output->writeln("Not possible to rebuild future dates, input provided: $q_response");
            return 1;
        }

        $pr_rpc = new PR($this->getSilexApplication());

        //$date = Carbon::create(2017, 5, 2);

        $this->output->writeln("Deleting wrong balances");
        DB::connection()->table('users_daily_balance_stats')->where('source', 1)->where('date', '>', $date->toDateString())->delete(); //Balances on 3 deleted
        $this->output->writeln("Wrong balances deleted");

        $companies = collect($pr_rpc->execFetch("SELECT company_id AS user_id, currency, country FROM companies"))->keyBy('user_id')->all();

        $init_balance_list = DB::connection()->table('users_daily_balance_stats')->where('source', 1)->where('date', $date->toDateString())->get(); //Get balances on the 2

        $balance_list = [];
        foreach ($init_balance_list as $row) {
            $balance_list[intval($row->user_id)] = intval($row->cash_balance);
        }


        while ($date->isPast() || $date->isToday()) {
            $this->output->write($date->toDateString());
            $insert = [];

            /** @var Collection $list */
            $trans_list = collect($pr_rpc->execFetch("SELECT u.company_id AS user_id, sum(amount) AS net
                                        FROM cash_transactions ct
                                        LEFT JOIN users u ON u.id = ct.user_id
                                        WHERE ct.transactiontype IN (5, 8, 20, 13)
                                              AND ct.timestamp BETWEEN '{$date->toDateString()} 00:00:00' AND '{$date->toDateString()} 23:59:59'
                                              AND ct.amount != 0
                                        GROUP BY user_id"))->keyBy('user_id')->all();

            foreach ($trans_list as $user => $data) {
                if ($data['net'] == 0) {
                    continue;
                }

                if (isset($balance_list[$user])) {
                    $balance_list[$user] += $data['net'];

                } else {
                    $balance_list[$user] = $data['net'];
                }
            }


            foreach ($balance_list as $user => $balance) {
                    $insert[] = [
                        'date' => $date->copy()->addDay()->toDateString(),
                        'user_id' => $user,
                        'cash_balance' => $balance,
                        'bonus_balance' => 0,
                        'currency' => $companies[$user]['currency'],
                        'country' => $companies[$user]['country'],
                        'source' => 1
                    ];
            }

            if (!empty($insert)) {
                $in_res = UserDailyBalance::bulkInsert($insert, 'user_id', DB::connection());
                $this->output->writeln(' result: '. var_export($in_res, true));
            }

            $date->addDay();
        }


        $this->output->writeln("Done.");

        return 0;
    }

}