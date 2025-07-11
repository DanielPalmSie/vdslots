<?php

namespace App\Commands;

use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnitTestCommand extends Command
{
    protected function configure()
    {
        $this->setName("test")
            ->setDescription("Run the application tests")
            ->addArgument('path', null, 'Run specified UnitTest [UnitTest.php]')
            ->setHelp(<<<EOT
The <info>test</info> command runs all tests or specified one as argument 
<info>./console test</info>
<info>./console test <path></info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(shell_exec('php vendor/bin/codecept run3 unit '. $input->getArgument('path')));

        return 0;
    }
}
