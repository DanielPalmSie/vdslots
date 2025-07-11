<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddLossesMessage extends Migration
{
    private $localized_strings_table;

    private array $items = [
        [
            'alias'    => 'my.all.time.losses',
            'language' => 'en',
            'value'    => 'Losses'
        ]
    ];

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

        foreach ($this->items as $item) {
            /*  if this record alreay exists in localized_strings table then we skip inserting it again*/
            $exists = $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', $item['alias'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            /* insert the record*/
            $this->connection
                ->table($this->localized_strings_table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->items as $item) {
            $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', $item['alias'])
                ->delete();
        }
    }
}