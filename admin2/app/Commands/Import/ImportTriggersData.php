<?php

namespace App\Commands\Import;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportTriggersData extends ImportBaseClass
{
    protected const TABLE = 'triggers';
    protected const EXPECTED_HEADERS = ['name', 'indicator_name', 'description', 'color', 'score', 'ngr_threshold'];

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('import:triggers')
            ->setDescription('Imports data into `triggers` data from a CSV file')
            ->addArgument('file_path', InputArgument::REQUIRED, 'The path to the CSV file');
    }

    /**
     * @param array $row
     */
    protected function getExistingRecord(array $row): bool
    {
        return $this->connection
            ->table(self::TABLE)
            ->where('name', '=', $row['name'])
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
            ->where('name', '=', $row['name'])
            ->update([
                'indicator_name' => $row['indicator_name'],
                'description' => $row['description'],
                'color' => $row['color'],
                'score' => $row['score'],
                'ngr_threshold' => $row['ngr_threshold']
            ], true);
    }

    /**
     * @param array $row
     * @param $model
     * @return bool
     */
    protected function modelShouldBeUpdated(array $row, $model): bool
    {
        if ($row['indicator_name'] === $model->indicator_name && $row['description'] === $model->description && $row['color'] === $model->color && $row['score'] === $model->score && $row['ngr_threshold'] === $model->ngr_threshold) {
            return true;
        }

        return false;
    }
}
