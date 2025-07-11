<?php


namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculateBalanceStats extends LiabilityCommand
{
    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:recalculate:stats")
            ->setDescription("Recalculate balance stats from the master to the nodes")
            ->addArgument('day', InputArgument::REQUIRED, 'Day');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!DB::isMasterAndSharded('users_daily_balance_stats')) {
            throw new \Exception("Table must be sharded and master");
        }

        $date = Carbon::parse($input->getArgument('day'));

        /** @var Connection $master */
        $master = DB::connection('default');

        $master->setFetchMode(\PDO::FETCH_ASSOC);

        $data = $master->select("SELECT user_id, date, cash_balance, bonus_balance, currency, country, province,
                source  FROM videoslots.users_daily_balance_stats WHERE date = '{$date}'");
        foreach ($data as &$d) {
            if (cu($d["user_id"])) {
                $d['province'] = cu($d["user_id"])->getMainProvince();
            }
        }
        DB::loopNodes(function (Connection $connection) use ($date) {
            return $connection->delete("DELETE FROM videoslots.users_daily_balance_stats WHERE date = '{$date}'");
        });

        DB::bulkInsert('users_daily_balance_stats', 'user_id', $data, null, true);

        $this->output->writeln("Done");

        return 0;
    }
}
