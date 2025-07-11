<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddDepositIncreaseErrorTranslation extends Migration
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
                    'alias' => 'deposit.limit.increase.error',
                    'language' => 'en',
                    'value' => 'You are temporarily restricted from increasing your deposit limits. Please contact our Customer Service via live chat or email (support@videoslots.com) if you have any further questions.'
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
            ->whereIn('alias', ['deposit.limit.increase.error',])
            ->where('language', '=', 'en')
            ->delete();
    }
}