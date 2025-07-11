<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */
namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Repositories\LiabilityRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityRecalculateAllCommand extends LiabilityCommand
{
    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:recalculate:all")
            ->setDescription("Liability Job: recalculate the data given a year and a month.")
            ->addArgument('year', InputArgument::REQUIRED, 'Year')
            ->addArgument('month', InputArgument::REQUIRED, 'Month')
            ->addArgument('no-bets', InputArgument::OPTIONAL, '[-nb]');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $year = (int)$input->getArgument('year');
        $month = (int)$input->getArgument('month');
        if (!empty($input->getArgument('no-bets'))) {
            $not_in = [LiabilityRepository::CAT_BETS, LiabilityRepository::CAT_WINS, LiabilityRepository::CAT_FRB_WINS];
            $no_bets_wins = true;
        } else {
            $not_in = [];
            $no_bets_wins = null;
        }

        if ($year < 2015 || $month > 12 || $month < 1) {
            $this->output->writeln(
                "{$this->now()} Year must equal or greater than 2015 and month must be between 1 and 12"
            );
            return 1;
        }

        $this->deleteData($year, $month, [], $not_in);
        $this->output->writeln("{$this->now()} Previously inserted data deleted.");

        $this->generateData($year, $month, $no_bets_wins);

        return 0;
    }

}