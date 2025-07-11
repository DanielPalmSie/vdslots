<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddJurisdictionPopupMessageMrVegas extends Migration
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
                    'alias'    => 'new.jurisdiction.popup.message',
                    'language' => 'en',
                    'value'    => 'You are now entering a website under Maltese jurisdiction and is licensed within the EU to operate online gambling. When playing at MrVegas you play under the Maltese regulation authorized by the Malta Gaming Authority (https://www.mga.org.mt/).'
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
            ->where('alias', 'new.jurisdiction.popup.message')
            ->delete();
    }
}