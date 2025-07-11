<?php

namespace App\Commands\Helpers;

use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Connection;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPTOCommand extends Command
{

    protected function configure()
    {
        $this->setName("database:cleanup_pto")
            ->setDescription("Clean up changes from interrupted percona-toolkit migration.")
            ->addArgument('table', InputArgument::REQUIRED, 'Table to cleanup');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DB::loopNodes(static function (Connection $connection) use ($input, $output) {
            $database_name = $connection->getDatabaseName();
            $table_name = $input->getArgument('table');
            $schema_builder = $connection->getSchemaBuilder();

            $output->writeln("Cleanup triggers on {$database_name}.{$table_name}");

            $connection->statement("DROP TRIGGER IF EXISTS {$database_name}.pt_osc_{$database_name}_{$table_name}_del");
            $connection->statement("DROP TRIGGER IF EXISTS {$database_name}.pt_osc_{$database_name}_{$table_name}_upd");
            $connection->statement("DROP TRIGGER IF EXISTS {$database_name}.pt_osc_{$database_name}_{$table_name}_ins");

            $output->writeln("Cleanup temporal table");
            $schema_builder->dropIfExists("_{$table_name}_new");

        }, true);

        return 0;
    }
}