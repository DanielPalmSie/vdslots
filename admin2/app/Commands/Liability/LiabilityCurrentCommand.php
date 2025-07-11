<?php

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Models\UserMonthlyLiability;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityCurrentCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:current")
            ->setDescription("Liability Job: generate data for the Current month until the 20th.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $day = 20;

        $data_query = UserMonthlyLiability::where('year', $year)->where('month', $month);
        $count = $data_query->count();
        if ($count > 0) {
            $output->writeln("{$this->now()} $count rows found, removing data from that month.");
            $data_query->delete();
            $output->writeln("{$this->now()} Month deleted.");
        } else {
            $output->writeln('{$this->now()} No existing rows found for the current month.');
        }

        $this->generateData($year, $month, false, $day);

        return 0;
    }

}
