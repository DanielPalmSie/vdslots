<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Models\User;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityCheckAutoUserCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    /** @var  InputInterface $input */
    protected $input;

    protected $data = [];

    protected function configure()
    {
        $this->setName("liability:check:auto")
            ->setDescription("Liability Job: check liability for a given customer.")
            ->addArgument('argument', InputArgument::REQUIRED, 'User id or username')
            ->addArgument('start', InputArgument::REQUIRED, 'Start date with Y-m-d format')
            ->addArgument('end', InputArgument::OPTIONAL, 'End date with Y-m-d format, if not specified, it will check only the day provided on start');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $argument = $input->getArgument('argument');
        if (is_numeric($argument)) {
            $user = User::find($argument);
        } else {
            $user = User::findByUsername($argument);
        }

        if (empty($user)) {
            $output->writeln("User not found");
            return 1;
        }

        $start = Carbon::parse($input->getArgument('start'));

        if (!empty($input->getArgument('end'))) {
            $end = Carbon::parse($input->getArgument('end'));
            while ($start->lessThanOrEqualTo($end)) {
                $this->checkDay($user, $start);
                $start->addDay();
            }
            dd("ENDED");
        } else {
            dd($this->checkDay($user, $start));
        }

        return 0;
    }

    private function checkDay($user, Carbon $date)
    {
        $queries = LiabilityRepository::getUserTransactionListQueries($user, $date, true);

        $balance = $start_balance = DB::select("SELECT IFNULL(sum(cash_balance) + sum(bonus_balance),0) AS bal FROM users_daily_balance_stats WHERE source = 0 AND user_id = :user_id AND date = :b_date", [
            'user_id' => $user->id,
            'b_date' => $date->toDateString()
        ])[0]->bal;

        $data = $queries['bets']->union($queries['wins'])->union($queries['cash'])->orderBy('date', 'asc')->get();

        $i = $sum = $previous_match = 0;
        foreach ($data as $elem) {
            $i++;
            $sum += $elem->amount;
            $balance += $elem->amount;

            if (is_numeric($elem->type)) {
                if ($elem->balance != $balance) {
                    $this->output->writeln("Trans " . json_encode($elem));
                    //$diff = $previous_match->balance + ;
                    dd("MISMATCH, FROM {$previous_match->id} TO {$elem->id}");
                } else {
                    $previous_match = $elem;
                }
            }
        }

        $end_balance = DB::select("SELECT date, IFNULL(sum(cash_balance) + sum(bonus_balance),0) AS bal FROM users_daily_balance_stats WHERE source = 0 AND user_id = :user_id AND date = :b_date", [
            'user_id' => $user->id,
            'b_date' => $date->copy()->addDay()->toDateString()
        ])[0]->bal;

        return [
            'start balance' => $start_balance,
            'net' => $sum,
            'end_balance' => $end_balance,
            'calculated balance ' => $balance
        ];
    }

}