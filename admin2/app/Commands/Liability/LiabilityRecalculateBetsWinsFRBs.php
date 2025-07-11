<?php

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

class LiabilityRecalculateBetsWinsFRBs extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:recalculate:betswinsfrbs")
            ->setDescription("Liability Job: recalculate the data bets, wins & frbs for year and month.")
            ->addArgument('year', InputArgument::OPTIONAL, 'Year')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $year = (int)$input->getArgument('year');
        $month = (int)$input->getArgument('month');

        if (empty($year) || empty($month)) {
            $this->output->writeln('Not enough arguments (missing: "year, month")');
            return;
        }

        $repo = new LiabilityRepository($year, $month);

        $this->output->writeln("Deleting Bets");
        $del_res = $this->deleteData($repo->getYear(), $repo->getMonth(), [LiabilityRepository::CAT_BETS]);
        if (empty($del_res)) {
            $this->output->writeln("Error deleting previous data or no records to delete.");
        } else {
            $this->output->writeln("Delete success.");
        }

        $this->output->writeln("Deleting Wins");
        $del_res = $this->deleteData($repo->getYear(), $repo->getMonth(), [LiabilityRepository::CAT_WINS]);
        if (empty($del_res)) {
            $this->output->writeln("Error deleting previous data or no records to delete.");
        } else {
            $this->output->writeln("Delete success.");
        }

        $this->output->writeln("Deleting FRBs");
        $del_res = $this->deleteData($repo->getYear(), $repo->getMonth(), [LiabilityRepository::CAT_FRB_WINS]);
        if (empty($del_res)) {
            $this->output->writeln("Error deleting previous data or no records to delete.");
        } else {
            $this->output->writeln("Delete success.");
        }

        $this->output->writeln("{$this->now()} Starting liability data generation for year $year and month $month. ");

        $callback = function (Connection $connection) use ($repo) {

            $this->output->writeln("{$this->now()} Processing: VS {$connection->getName()}");

            $connection->setFetchMode(\PDO::FETCH_ASSOC);

            $this->data['connection'] = $connection;
            $repo->setConnection($connection);

            LiabilityCommand::insertData($repo->getBetsAndWins($repo::CAT_BETS));
            LiabilityCommand::insertData($repo->getBetsAndWins($repo::CAT_WINS));
            LiabilityCommand::insertData($repo->getBetsAndWins($repo::CAT_FRB_WINS));
        };

        if (DB::isSharded((new UserMonthlyLiability())->getTable())) {
            DB::loopNodes($callback); //table will be global and sharded
        } else {
            $callback(DB::getMasterConnection());
        }

        $this->output->writeln("Liability bets, wins and frb recalculation done");
    }
}