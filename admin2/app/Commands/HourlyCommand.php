<?php

declare(strict_types=1);

namespace App\Commands;

use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HourlyCommand extends Command
{
    protected function configure()
    {
        $this->setName("hourly")
            ->setDescription("Jobs that should run every hour");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $app */
        $app = $this->getSilexApplication();

        $app['monolog']->addError("Command: hourly - Started");

        $rg_evaluation_first_iteration_command = $this->getApplication()->find('rg:evaluation-first-iteration');
        $rg_evaluation_first_iteration_command->run($input, $output);
        $app['monolog']->addError('Command: hourly - rg:evaluation-first-iteration');

        $rg_evaluation_second_iteration_command = $this->getApplication()->find('rg:evaluation-second-iteration');
        $rg_evaluation_second_iteration_command->run($input, $output);
        $app['monolog']->addError('Command: hourly - rg:evaluation-second-iteration');

        return 0;
    }

}
