<?php


namespace App\Commands\Import;

use App\Extensions\Database\FManager as DB;
use Ivoba\Silex\Command\Command;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Import extends Command
{
    protected const MASTER_TABLE_PREFIX = 'm';

    protected function configure()
    {
        $this->setName('import')
            ->setDescription('Imports data from CSV created by the export:* commands')
            ->addArgument('data_src', InputArgument::REQUIRED, 'The folder with the csv files')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Truncate the tables before inserting data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = new Finder();
        $files
            ->files()
            ->filter(function (SplFileInfo $file) {
                return $file->getExtension() === '';
            })
            ->in($input->getArgument('data_src'));

        foreach ($files as $file) {
            $output->writeln('Importing: ' . $file->getFilename());

            $filename = $file->getRealPath();
            /** @noinspection PhpUnusedLocalVariableInspection */
            list($unused, $table, $shard) = explode('__', $filename);

            if ($shard === self::MASTER_TABLE_PREFIX) {
                $conn = DB::getMasterConnection();
            } else {
                $conn = DB::connectionByKey($table, $shard);
            }

            $headers = implode(',', $file->openFile('r')->fgetcsv());

            $conn->statement('SET SESSION unique_checks=0');
            $conn->statement('SET SESSION foreign_key_checks=0');

            if ($input->getOption('replace')) {
                $conn->table($table)->truncate();
            }

            $conn->statement("
                LOAD DATA INFILE '{$filename}'
                INTO TABLE {$table}
                FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\\\\'
                IGNORE 1 LINES
                ({$headers})
            ");
        }

        return 0;
    }
}