<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddDocumentTypeErrorMessage extends Migration
{
    private $localized_strings_table;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';

        $this->connection = DB::getMasterConnection();
    }
    
    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table($this->localized_strings_table)->insert(
            [
                [
                    'alias' => 'other.document.born.italy.error',
                    'language' => 'en',
                    'value' => 'This field is not available for players born in Italy.'
                ],
                [
                    'alias' => 'other.document.born.italy.error',
                    'language' => 'it',
                    'value' => 'Campo non disponibile per soggetti nati in Italia.'
                ]
            ]
        );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->localized_strings_table)
            ->where('alias', 'other.document.born.italy.error')
            ->delete();
    }
}