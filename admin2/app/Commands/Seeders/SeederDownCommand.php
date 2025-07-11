<?php

namespace App\Commands\Seeders;

use App\Extensions\Database\Seeder\SeederBootstrapTrait;
use Phpmig\Console\Command\DownCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute seeder command
 */
class SeederDownCommand extends DownCommand
{
    use SeederBootstrapTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('seeder:down')
             ->setDescription('Revert a specific seeder')
             ->setHelp(<<<EOT
The <info>down</info> command revert a specific seeder

<info>./console seeder:down 20111018185121</info>

EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);

        $migrations = $this->getMigrations();
        $versions = $this->getAdapter()->fetchAll();
        $version = $input->getArgument('version');

        if (!in_array($version, $versions)) {
            $output->writeln(
                "<error>This seeder does not exist. </error>"
            );
            return 1;
        }

        if (!isset($migrations[$version])) {
            return 1;
        }

        $container = $this->getContainer();
        echo $container['phpmig.migrator']->down($migrations[$version]);

        return 0;
    }

}

