<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\UserMonthlyLiability;
use App\Repositories\LiabilityRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityRecalculateCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:recalculate:one")
            ->setDescription("Liability Job: recalculate the data given one category, year and month.")
            ->addArgument('year', InputArgument::OPTIONAL, 'Year')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month')
            ->addArgument('cat', InputArgument::OPTIONAL, 'Category Name')
            ->addOption('list', ['-L'], InputOption::VALUE_NONE, 'List Supported Categories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $year = (int)$input->getArgument('year');
        $month = (int)$input->getArgument('month');
        $category = $input->getArgument('cat');

        $supported_categories_map = [
            'deposits',
            'withdrawals'
        ];

        $connection = DB::connection();
        $connection->setFetchMode(\PDO::FETCH_ASSOC);
        $repo = new LiabilityRepository($year, $month);
        $repo->setConnection($connection);

        if (!empty($input->getOption('list'))) {
            $this->output->writeln("Supported categories list:");
            foreach ($supported_categories_map as $category) {
                $this->output->writeln($category);
            }
            return 1;
        }

        if (empty($year) || empty($month) || empty($category)) {
            $this->output->writeln('Not enough arguments (missing: "year, month, category")');
            return 1;
        }

        if (!in_array($category, $supported_categories_map)) {
            $this->output->writeln("Wrong or not supported category.");
            return 1;
        }

        try {
            $this->{'recalc' . ucwords($category)}($repo);
        } catch (\Exception $e) {
            $this->output->writeln("Error: {$e->getMessage()}");
            return 1;
        }

        $this->output->writeln("{$category} done");

        return 0;
    }

    protected function recalcDeposits(LiabilityRepository $repo)
    {
        dd('recalculations on deposits not supported yet');
/* TODO see how withdrawals are done a do the same /Ricardo */
        /*
        $del_res = DB::table('users_monthly_liability')->where([
            'source' => 0,
            'year' => $repo->getYear(),
            'month' => $repo->getMonth()
        ])->whereRaw('(main_cat = 1 OR sub_cat = 3)')->delete();

        if (empty($del_res)) {
            throw new \Exception("Error deleting previous data");
        } else {
            $this->output->writeln("{$del_res} rows deleted.");
        }

        $this->insertData($repo->getDeposits());
        $this->insertData($repo->getDeposits(true));
        $this->insertData($repo->getMismatchedDeposits());
        */
    }

    /**
     * TODO refactor this to extract all common logic to be reusable in other categories functions
     * @param LiabilityRepository $repo
     * @throws \Exception
     */
    protected function recalcWithdrawals(LiabilityRepository $repo)
    {
        $this->output->writeln("Deleting withdrawals");
        $del_res = $this->deleteData($repo->getYear(), $repo->getMonth(), [LiabilityRepository::CAT_WITHDRAWAL]);

        if (empty($del_res)) {
            $this->output->writeln("Error deleting previous data or no records to delete.");
        } else {
            $this->output->writeln("Delete success.");
        }
        $this->output->writeln("Inserting withdrawals");

        $callback = function (Connection $connection) use ($repo) {

            $this->output->writeln("{$this->now()} Processing: VS {$connection->getName()}");

            $connection->setFetchMode(\PDO::FETCH_ASSOC);

            $this->data['connection'] = $connection;
            $repo->setConnection($connection);

            $this->insertData($repo->getWithdrawals());
        };

        if (DB::isSharded((new UserMonthlyLiability())->getTable())) {
            DB::loopNodes($callback); //table will be global and sharded
        } else {
            $callback(DB::getMasterConnection());
        }
    }

}