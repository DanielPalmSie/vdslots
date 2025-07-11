<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForPlaceOfBirth extends Migration
{
    private string $table = 'localized_strings';
    private Connection $connection;

    private array $strings
        = [
            [
                'alias'    => 'register.place.of.birth',
                'language' => 'en',
                'value'    => 'Place Of Birth',
            ],
            [
                'alias'    => 'place.of.birth.error.required',
                'language' => 'en',
                'value'    => 'Place Of Birth is required',
            ],
        ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->strings as $string) {
            $exists = $this->connection
                ->table($this->table)
                ->where('alias', $string['alias'])
                ->where('language', $string['language'])
                ->first();

            if (!empty($exists)) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', '=', $string['alias'])
                    ->where('language', '=', $string['language'])
                    ->delete();
            }

            $this->connection->table($this->table)->insert($string);
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->strings as $string) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->delete();
        }

    }
}
