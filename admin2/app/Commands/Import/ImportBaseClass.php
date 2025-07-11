<?php

namespace App\Commands\Import;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Ivoba\Silex\Command\Command;
use SplFileObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Exception;

abstract class ImportBaseClass extends Command
{
    private SplFileObject $csvFile;
    private array $headers;
    private array $data;
    private string $filePath;
    protected $connection;
    private $app;
    private string $log_info = '';
    private OutputInterface $output;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->app = $this->getSilexApplication();
        $this->log_info = "Ran: " . json_encode($input->getArguments());
        $this->output = $output;
        $this->filePath = $input->getArgument('file_path');

        try {
            $this->setCsvFile();
            $this->setHeaders();
            $this->validateHeaders();

            $this->setDataForUpserts();
            $this->loopShardsAndUpsertData($output, $input);
        } catch (RuntimeException $exception) {
            $this->output->writeln($exception->getMessage());
            $this->app['monolog']->addError($this->log_info . " failed due to {$exception->getMessage()} | {$exception->getTrace()} ");

            return 1;
        }

        return 0;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Delete records from the table before inserting data');
    }


    /**
     * @return void
     */
    private function setCsvFile(): void
    {
        if (!file_exists($this->filePath)) {
            throw new RuntimeException("File not found: $this->filePath");
        }

        $this->csvFile = new SplFileObject($this->filePath);
        $this->csvFile->setFlags(SplFileObject::READ_CSV);
    }

    /**
     * @return void
     */
    private function setHeaders(): void
    {
        $this->headers = $this->csvFile->fgetcsv();

        if ($this->headers === false) {
            throw new RuntimeException("Failed to read headers from CSV file: $this->filePath");
        }

    }

    /**
     * @return void
     */
    private function validateHeaders(): void
    {
        if ($this->headers !== static::EXPECTED_HEADERS) {
            throw new RuntimeException("CSV headers do not match the expected headers.");
        }
    }

    /**
     * @return void
     */
    private function setDataForUpserts(): void
    {
        $rowNumber = 2;

        while (!$this->csvFile->eof()) {
            $row = $this->csvFile->fgetcsv();

            if ($this->checkIfCsvDataRowIsEmpty($row)) {
                $this->output->writeln("Warning: Skipping row {$rowNumber} because it's empty: $this->filePath");

                continue;
            }

            if ($this->checkIfCsvDataRowMatchesHeaderCount($row)) {
                $this->data[] = array_combine($this->headers, $row);
            } else {
                $this->output->writeln("Warning: Skipping row {$rowNumber} with mismatched column count in file: $this->filePath");
            }

            $rowNumber++;
        }
    }

    /**
     * @param $row
     * @return bool
     */
    private function checkIfCsvDataRowIsEmpty($row): bool
    {
        return $row === [null] || $row === false;
    }

    /**
     * @param $row
     * @return bool
     */
    private function checkIfCsvDataRowMatchesHeaderCount($row): bool
    {
        return count($row) === count($this->headers);
    }

    /**
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return void
     */
    private function loopShardsAndUpsertData(OutputInterface $output, InputInterface $input): void
    {
        DB::loopNodes(function (Connection $connection) use ($input, $output) {
            $this->setDbConnection($connection);
            $this->connection->beginTransaction();
            $table = static::TABLE;

            try {
                if ($this->shouldDeleteData($input)) {
                    $this->connection->delete("DELETE FROM {$table}");

                    $output->writeln("Deleted $table on {$this->connection->getDatabaseName()}");
                }

                $this->insertOrUpdateData($input);
                $this->connection->commit();
                $output->writeln("Upserted records on $table on {$this->connection->getDatabaseName()}");
            } catch (Exception $exception) {
                $this->connection->rollBack();

                $output->writeln("Something went wrong: {$exception->getMessage()}");
            }

        }, true);
    }

    /**
     * @param $connection
     * @return void
     */
    private function setDbConnection($connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function shouldDeleteData(InputInterface $input): bool
    {
        return $input->getOption('delete');
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function insertOrUpdateData(InputInterface $input): void
    {
        foreach ($this->data as $row) {
            if ($this->shouldDeleteData($input) || !$this->getExistingRecord($row)) {
                $this->connection->table(static::TABLE)->insert($row, true, true);
            } else {
                $this->updateExistingRecord($row);
            }
        }
    }

    /**
     * @param array $row
     */
    abstract protected function getExistingRecord(array $row): bool;

    /**
     * @param array $row
     * @return void
     */
    abstract protected function updateExistingRecord(array $row): void;

    /**
     * @param array $row
     * @param $model
     * @return bool
     */
    abstract protected function modelShouldBeUpdated(array $row, $model): bool;

}
