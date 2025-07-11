<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddEsProfileTranslations extends Migration
{
    private $localized_strings_table;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->localized_strings_connection_table = 'localized_strings_connections';

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
                    'alias' => 'account.nationality',
                    'language' => 'en',
                    'value' => 'Nationality'
                ],
                [
                    'alias' => 'account.residence_country',
                    'language' => 'en',
                    'value' => 'Place of Residence'
                ],
                [
                    'alias' => 'account.full_last_name',
                    'language' => 'en',
                    'value' => 'Full Last Name'
                ],
                [
                    'alias' => 'account.fiscal_region',
                    'language' => 'en',
                    'value' => 'Fiscal Region'
                ],
                [
                    'alias' => 'account.fiscal_identification_number',
                    'language' => 'en',
                    'value' => 'Fiscal Identification Number'
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
            ->whereIn('alias', ['account.nationality', 'account.residence_country', 'account.full_last_name', 'account.fiscal_region','account.fiscal_identification_number'])
            ->where('language', '=', 'en')
            ->delete();
    }
}