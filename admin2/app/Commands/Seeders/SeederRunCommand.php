<?php

namespace App\Commands\Seeders;

use App\Extensions\Database\Seeder\SeederBootstrapTrait;
use Phpmig\Console\Command\MigrateCommand;

/**
 * Run seeders command
 */
class SeederRunCommand extends MigrateCommand
{
    use SeederBootstrapTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('seeder:run')
             ->setDescription('Run all seeders')
             ->setHelp(<<<EOT
The <info>seeder:run</info> command runs all available seeders

<info>./console seeder:run</info>

EOT
            );
    }
}
