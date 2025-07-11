<?php

use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class ImportLocalizedStringsDataToMrVegas extends SeederTranslation
{
    private Connection $connection;

    private string $localized_strings_table;

    private string $localized_strings_connections_table;

    private array $localized_strings_data;

    private array $localized_strings_connections_data;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->localized_strings_connections_table = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();

        $this->localized_strings_data = $this->csvToArray('/tmp/localized_strings.csv') ?: [];
        $this->localized_strings_connections_data = $this->csvToArray('/tmp/localized_strings_connections.csv') ?: [];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        foreach ($this->localized_strings_data as $entry) {
            $exists = $this->connection->table($this->localized_strings_table)
                ->where('alias', $entry[0])
                ->where('language', $entry[1])
                ->first();

            if (!$exists) {
                $this->connection->table($this->localized_strings_table)->insert(
                    [
                        [
                            'alias'    => $entry[0],
                            'language' => $entry[1],
                            'value'    => $entry[2]
                        ]
                    ]
                );
            }
        }

        foreach ($this->localized_strings_connections_data as $entry) {
            $exists = $this->connection->table($this->localized_strings_connections_table)
                ->where('target_alias', $entry[0])
                ->where('bonus_code', $entry[1])
                ->where('tag', $entry[2])
                ->first();

            if (!$exists) {
                $this->connection->table($this->localized_strings_connections_table)->insert(
                    [
                        [
                            'target_alias' => $entry[0],
                            'bonus_code' => $entry[1],
                            'tag' => $entry[2]
                        ]
                    ]
                );
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        $this->connection
            ->table($this->localized_strings_table)
            ->where('alias', 'LIKE', 'sb.%')
            ->delete();

        $this->connection
            ->table($this->localized_strings_connections_table)
            ->where('target_alias', 'LIKE', 'sb.%')
            ->delete();
    }

    /**
     * Export data from CSV file to array
     *
     * @param string $csv_file
     * @return array|false
     */
    private function csvToArray(string $csv_file)
    {
        if(!file_exists($csv_file) || !is_readable($csv_file)) {
            return false;
        }

        $data = [];
        if (($handle = fopen($csv_file, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }

}