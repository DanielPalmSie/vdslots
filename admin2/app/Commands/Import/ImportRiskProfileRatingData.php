<?php

namespace App\Commands\Import;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportRiskProfileRatingData extends ImportBaseClass
{
    protected const TABLE = 'risk_profile_rating';
    protected const EXPECTED_HEADERS = [
        'name',
        'jurisdiction',
        'title',
        'type',
        'score',
        'category',
        'section',
        'data'
    ];

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('import:risk-profile-rating')
            ->setDescription('Imports data into `risk_profile_rating` data from a CSV file')
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
            ->where('jurisdiction', '=', $row['jurisdiction'])
            ->where('category', '=', $row['category'])
            ->where('section', '=', $row['section'])
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
            ->where('jurisdiction', '=', $row['jurisdiction'])
            ->where('category', '=', $row['category'])
            ->where('section', '=', $row['section'])
            ->update([
                'title' => $row['title'],
                'type' => $row['type'],
                'score' => $row['score'],
                'data' => $row['data'],
            ], true);
    }

    /**
     * @param array $row
     * @param $model
     * @return bool
     */
    protected function modelShouldBeUpdated(array $row, $model): bool
    {
        if ($row['title'] === $model->title && $row['type'] === $model->type && $row['score'] === $model->score && $row['data'] === $model->data) {
            return true;
        }

        return false;
    }
}
