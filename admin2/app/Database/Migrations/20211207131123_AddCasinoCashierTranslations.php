<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddCasinoCashierTranslations extends Migration
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
                    'alias' => 'cashier.error.bank_account_number.format',
                    'language' => 'en',
                    'value' => 'Bank account number must contain 6-9 digits for JP'
                ],
                [
                    'alias' => 'cashier.error.bank_code.format',
                    'language' => 'en',
                    'value' => 'Bank code must be 4 characters for JP'
                ],
                [
                    'alias' => 'cashier.error.fulladdress.max',
                    'language' => 'en',
                    'value' => 'Full address cannot be longer than 64 symbols'
                ],
                [
                    'alias' => 'cashier.error.zipcode.dash',
                    'language' => 'en',
                    'value' => 'Postcode must contain "-" symbol'
                ],
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
            ->whereIn('alias', ['cashier.error.bank_account_number.format', 'cashier.error.bank_code.format', 'cashier.error.fulladdress.max', 'cashier.error.zipcode.dash'])
            ->where('language', '=', 'en')
            ->delete();
    }
}