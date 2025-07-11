<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */
namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Models\UserMonthlyLiability;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityLastCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:last")
            ->setDescription("Liability Job: generate data for the last month.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $now = Carbon::now()->subMonth();
        $year = $now->year;
        $month = $now->month;


        $data_query = UserMonthlyLiability::where('year', $year)->where('month', $month);
        $count = $data_query->count();
        if ($count > 0) {
            $this->output->writeln("{$this->now()} $count rows found, removing data from that month.");
            $data_query->delete();
            $this->output->writeln("{$this->now()} Month deleted.");
        }
        $this->generateData($year, $month);

        return 0;
    }

}