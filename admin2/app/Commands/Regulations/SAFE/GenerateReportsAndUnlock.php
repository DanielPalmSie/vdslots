<?php

namespace App\Commands\Regulations\SAFE;

use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateReportsAndUnlock extends Command
{
    protected function configure()
    {
        $this->setName('regulations:safe:generate_reports_and_unlock');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Resetting DGA reporting');
        phive('Licensed/DK/DK')->generateReportsAndUnlock();

        return 0;
    }
}
