<?php


namespace App\Commands;

use App\Repositories\AccountingRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculateExtraBalance extends Command
{
    /** @var  OutputInterface $output */
    protected $output;

    /** @var  InputInterface $input */
    protected $input;

    protected function configure()
    {
        $this->setName("recalculate:extrabalance")
            ->setDescription("Recalculate extra balance")
            ->addArgument('start', InputArgument::REQUIRED, 'Start date with Y-m-d format')
            ->addArgument('end', InputArgument::OPTIONAL, 'End date with Y-m-d format, if not specified, it will add todays date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $input->getArgument('start');
        $end =  $input->getArgument('end') ?? Carbon::today()->toDateString();
        $account_repo = new AccountingRepository();
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            echo "recalculating ". $date->format('Y-m-d'). "\n";
            echo "--------------------------------- \n";
            $account_repo->addExtraBalanceToPlayerBalanceCache($date->format('Y-m-d'));
        }
        return 0;
    }
}
