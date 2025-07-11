<?php

namespace App\Commands\Checks;

use App\Extensions\Database\ReplicaFManager as DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;

class DatabaseIntegrityReportCommand extends AbstractDatabaseIntegrityCommand
{
    protected array $data = [];
    protected array $csv = [];

    protected string $file_path;

    protected function configure(): void
    {
        parent::configure();

        $this->setName('data-integrity:report')
            ->setDescription('Checks database for broken/faulty data and stores them into csv files.')
            ->addOption(
                'file_path',
                null,
                InputArgument::OPTIONAL,
                'Full file path where the CSV should stored',
                getenv('STORAGE_PATH') . '/' . static::STORAGE_PATH . '/'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->file_path = $input->getOption('file_path') ?? $this->getFilePath();
    }

    public function runCheck(
        DatabaseCheckInterface $database_check,
        InputInterface $input,
        OutputInterface $output
    ): void {

        $this->clearData();
        $this->collectDataFromShardTables($database_check);

        if (empty($this->data)) {
            $output->writeln("[{$database_check->name()}] <info>Ok</info>");
            return;
        }

        $this->prepareCsvData($database_check);
        $this->generateCsvFile($database_check, $output);
    }

    private function collectDataFromShardTables(DatabaseCheckInterface $database_check): void
    {
        $do_master = !count(DB::getNodesList());

        if ($do_master) {
            $this->processNode(DB::getMasterConnection(), $database_check);
            return;
        }

        DB::loopNodes(
            function (Connection $connection) use ($database_check) {
                $this->processNode($connection, $database_check);
            });
    }

    private function processNode(Connection $connection, DatabaseCheckInterface $database_check): void
    {
        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        $builder = $this->getBuilder($database_check, $this->connection, $this->start_time, $this->end_time);

        if ($database_check->requiresUserData()) {
            $this->applyUsersQueries($builder);
        }

        $this->data = array_merge($this->data, $builder->get()->toArray());
    }

    protected function getBuilder(
        DatabaseCheckInterface $database_check,
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder {

        return $database_check->getBuilderForAll($connection, $start_time, $end_time);
    }

    private function generateCsvFile(
        DatabaseCheckInterface $database_check,
        OutputInterface $output
    ): void {

        if (!file_exists($this->file_path) && !mkdir($this->file_path, 0755, true)) {
            $this->app['monolog']->addError($this->log_info . ' Directory "%s" was not created');
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->file_path));
        }

        $fileName = $this->file_path . $this->generateFileName($database_check);
        $f = fopen($fileName, 'x+');

        if (!$f) {
            $errorMsg = sprintf(
                'Cant create the file at "%s". Check if a file with the same name already exists',
                $fileName
            );
            $this->app['monolog']->addError($this->log_info . ' ' . $errorMsg);
            throw new RuntimeException($errorMsg);
        }

        foreach ($this->csv as $row) {
            fputcsv($f, $row);
        }

        fclose($f);

        $output->writeln("Successfully exported data on {$this->date}. File saved at {$fileName}");
    }

    private function addHeaders(DatabaseCheckInterface $database_check): void
    {
        $this->csv[] = $database_check->getHeaders();
    }

    private function prepareCsvData(DatabaseCheckInterface $database_check): void
    {
        $this->csv = [];
        $this->addHeaders($database_check);

        foreach ($this->data as $result) {
            $this->csv[] = array_values($result);
        }
    }

    private function generateFileName(DatabaseCheckInterface $database_check): string
    {
        return $database_check->name() . '_' . $this->date->format('Y_m_d') . '.csv';
    }

    private function getFilePath(): string
    {
        return getenv('STORAGE_PATH') . '/' . static::STORAGE_PATH . '/';
    }

    protected function clearData(): void
    {
        $this->data = [];
    }
}
