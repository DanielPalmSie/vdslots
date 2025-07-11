<?php

namespace App\Commands\Seeders;

use App\Extensions\Database\Seeder\SeederBootstrapTrait;
use Phpmig\Console\Command\UpCommand;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * Execute seeder command
 */
class SeederUpCommand extends UpCommand
{
    use SeederBootstrapTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('seeder:up')
             ->addOption('force', null,InputOption::VALUE_NONE, 'Force the execution of the specified seeder even if it has already been run before')
             ->setDescription('Run a specific seeder')
             ->setHelp(<<<EOT
The <info>up</info> command runs a specific seeder

<info>./console seeder:up 20111018185121</info>

EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);

        $migrations = $this->getMigrations();
        $versions   = $this->getAdapter()->fetchAll();

        $version = $input->getArgument('version');

        if ($input->getOption('force') === false && in_array($version, $versions)) {
            $output->writeln(
                "<error>This seeder has already been run. Use the --force option if you would like to force the execution anyway</error>"
            );
            return 1;
        }

        if (!isset($migrations[$version])) {
            return 1;
        }

        $container = $this->getContainer();
        $container['phpmig.migrator']->up($migrations[$version]);

        return 0;
    }
}



