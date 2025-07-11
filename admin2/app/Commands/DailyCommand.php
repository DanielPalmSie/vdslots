<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/16
 * Time: 12:28 PM
 */
namespace App\Commands;

use App\Repositories\AccountingRepository;
use App\Repositories\BetsAndWinsRepository;
use App\Repositories\UsersDailyBoosterStatsRepository;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Helpers\Common;

class DailyCommand extends Command
{
    protected function configure()
    {
        $this->setName("daily")
            ->setDescription("Daily Jobs to run at 00:00");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $account_repo = new AccountingRepository();
        $users_daily_booster_repo = new UsersDailyBoosterStatsRepository();

        /** @var Application $app */
        $app = $this->getSilexApplication();

        $app['monolog']->addError("Command: daily - Started");

        $res = $account_repo->cacheBalanceByPlayer();
        $app['monolog']->addError("Command: daily - cacheBalanceByPlayer res: ". var_export($res, true));

        $users_daily_booster_repo->cacheGeneratedBoosterByPlayer();
        $app['monolog']->addError("Command: daily - cacheGeneratedBoosterByPlayer done");

        // keep this as last, as it uses data from previously cached data (Ex. booster) to calculate the "extra_balance"
        $account_repo->addExtraBalanceToPlayerBalanceCache();
        $app['monolog']->addError("Command: daily - addExtraBalanceToPlayerBalanceCache done");

        $yesterday = Carbon::yesterday();
        $result = BetsAndWinsRepository::cacheUsersDailyUniqueBets($yesterday);
        $result_message = $result['message'];
        if ($result['success']) {
            $app['monolog']->addError("Command: daily - cacheUsersDailyUniqueBets($yesterday) done");

            try {
                loadPhive();
                phive('Cashier/Rg')->topXUniqueBetsCustomersRegisteredInLastYDays();
            } catch (\Exception $e){
                $message = $e->getMessage();
                $subject = "topXUniqueBetsCustomersRegisteredInLastYDays Failed: {$message}";
                $app['monolog']->addError($subject);

                Common::notifyCronFailure("topXUniqueBetsCustomersRegisteredInLastYDays", $message);
            }
        } else {
            $description = "cacheUsersDailyUniqueBets($yesterday) failed {$result_message}";
            $daily_log = "Command Error: daily - $description";
            $rg_flag_log =
                "Command Error: topXUniqueBetsCustomersRegisteredInLastYDays was skipped due to: {$description}";
            $app['monolog']->addError($daily_log);
            $app['monolog']->addError($rg_flag_log);

            Common::notifyCronFailure("topXUniqueBetsCustomersRegisteredInLastYDays", $rg_flag_log);
            Common::notifyCronFailure("cacheUsersDailyUniqueBets($yesterday)", $daily_log);
        }

        $data_integrity_command = $this->getApplication()->find('data-integrity:check');
        $params = [
            'date' => $yesterday->format('Y-m-d'),
            '--notify' => true
        ];
        $data_integrity_command->run(new ArrayInput($params), $output);
        $app['monolog']->addError("Command: daily - data-integrity:check done");

        return 0;
    }

}
