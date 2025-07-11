<?php

namespace App\Commands\Import;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportConfigData extends ImportBaseClass
{
    protected const TABLE = 'config';
    protected const EXPECTED_HEADERS = ['config_name','config_tag','config_value','config_type'];

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('import:config')
            ->setDescription('Imports data into `config` data from a CSV file')
            ->addArgument('file_path', InputArgument::REQUIRED, 'The path to the CSV file');
    }

    /**
     * @param array $row
     */
    protected function getExistingRecord(array $row): bool
    {
        return $this->connection
            ->table(self::TABLE)
            ->where('config_name', '=', $row['config_name'])
            ->where('config_tag', '=', $row['config_tag'])
            ->exists();
    }

    /**
     * @param array $row
     * @return void
     */
    protected function updateExistingRecord(array $row): void
    {
        $this->connection
            ->table(self::TABLE)
            ->where('config_name', '=', $row['config_name'])
            ->where('config_tag', '=', $row['config_tag'])
            ->update([
                'config_value' => $row['config_value'],
                'config_type' => $row['config_type'],
            ], true);
    }

    /**
     * @param array $row
     * @param $model
     * @return bool
     */
    protected function modelShouldBeUpdated(array $row, $model): bool
    {
        if ($row['config_value'] === $model->config_value && $row['config_type'] === $model->config_type) {
            return true;
        }

        return false;
    }
}
