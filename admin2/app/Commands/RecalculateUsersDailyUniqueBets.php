<?php


namespace App\Commands;

use App\Repositories\BetsAndWinsRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculateUsersDailyUniqueBets extends Command
{
    /** @var  OutputInterface $output */
    protected $output;

    /** @var  InputInterface $input */
    protected $input;

    protected function configure()
    {
        $this->setName("recalculate:users_daily_unique_bets")
            ->setDescription("Recalculate users daily unique bets")
            ->addArgument('start', InputArgument::REQUIRED, 'Start date with Y-m-d format')
            ->addArgument(
                'end',
                InputArgument::OPTIONAL,
                "End date with Y-m-d format, if not specified, it will add today's date"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $input->getArgument('start');
        $end =  $input->getArgument('end') ?? Carbon::today()->toDateString();

        $output->writeln("Recalculating users_daily_unique_bets.");

        $output->writeln("Start: $start");
        $output->writeln("End: $end");

        try {
            $period = CarbonPeriod::create($start, $end);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

        foreach ($period as $date) {
            $output->writeln("---------------------------------");
            $output->writeln("recalculating ". $date->format('Y-m-d'));
            $output->writeln("---------------------------------");

            BetsAndWinsRepository::cacheUsersDailyUniqueBets($date);
        }
        $output->writeln("Recalculating users_daily_unique_bets - DONE");
        return 0;
    }
}
