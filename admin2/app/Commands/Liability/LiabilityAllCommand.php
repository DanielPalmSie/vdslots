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
use Symfony\Component\Console\Question\ConfirmationQuestion;

class LiabilityAllCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    protected function configure()
    {
        $this->setName("liability:all")
            ->setDescription("Liability Job: generate the table from the scratch, from 1th January 2015.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (true) {
            $this->output->writeln('Command not available');
            return;
        }

        if (UserMonthlyLiability::first()) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Table is not empty. Do you want to truncate the user monthly liability cache table? ',
                true,
                '/^(y|j)/i'
            );

            if ($helper->ask($input, $output, $question)) {
                $this->output->write("{$this->now()} Truncating liability table... ");
                UserMonthlyLiability::truncate();
                $this->output->writeln("done.");
            } else {
                $this->output->writeln("{$this->now()} Ended without updating the table.");
                exit();
            }
        }

        $start = Carbon::create(2015, 1, 1, 0, 0, 0);

        while ($start < Carbon::now()->startOfMonth()) {
            $this->generateData($start->year, $start->month);
            $start->addMonth();
        }
    }

}