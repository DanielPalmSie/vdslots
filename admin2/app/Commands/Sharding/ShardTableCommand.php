<?php

namespace App\Commands\Sharding;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Schema\MysqlBuilder;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShardTableCommand extends Command
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        // drop sharded table command: console shard:table <table_name> --drop true
        $this->setName("shard:table")
            ->addArgument('table', InputArgument::REQUIRED, 'Table name')
            ->addArgument('with_data', InputArgument::OPTIONAL, 'With data', false)
            ->addOption('drop', 'd', InputOption::VALUE_OPTIONAL, 'Drop sharded table. All data from specified table will be lost.', false)
            ->setDescription("Shard one table");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $app */
        $app = $this->getSilexApplication();
        if ($app['env'] == 'prod') {
            $output->writeln('This command is not supported on the live environment.');
            return 1;
        }

        $table = $input->getArgument('table');
        $with_data = $input->getArgument('with_data');
        $should_drop = $input->getOption('drop');


        /** @var MysqlBuilder $schema */
        $schema = DB::schema();
        if ($should_drop) {
            // drop
        } elseif ($schema->hasTable($table, 'node0')) {
            $output->writeln('Table already sharded.');
            return 1;
        } elseif (!$schema->hasTable($table, 'default')) {
            $output->writeln('Table not available in the master.');
            return 1;
        }

        $output->writeln("Sharding '$table'");

        $structure = $should_drop
            ? "DROP TABLE $table"
            : DB::connection('default')->select("SHOW CREATE TABLE $table")[0]->{'Create Table'};

        $callback = function (Connection $connection) use ($structure) {
            return $connection->statement($structure);
        };

        if (DB::loopNodes($callback)) {
            $output->writeln("Table structure cloned successfully.");
        } else {
            $output->writeln("Table structure cloning failed.");
            return 1;
        }

        if ($with_data) {
            $output->writeln("Inserting the data.");
            DB::connection()->table($table)->orderBy('id')->chunk(1000, function($elements) use ($table, $output) {

                $elements = collect($elements)->map(function($el) {
                    return collect($el)->toArray();
                })->toArray();

                DB::bulkInsert($table, 'user_id', $elements);
            });
            $output->writeln("Data successfully inserted.");
        }

        return 0;
    }
}