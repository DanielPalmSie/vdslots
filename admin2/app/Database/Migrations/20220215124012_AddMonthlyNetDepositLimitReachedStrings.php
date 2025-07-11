<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddMonthlyNetDepositLimitReachedStrings extends Migration
{
    /** @var string */
    private $localized_strings_table;

    /** @var array */
    private $localized_strings_table_items;

    /** @var Connection */
    private $connection;


    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->connection = DB::getMasterConnection();

        $this->localized_strings_table_items = [
            [
                'alias' => 'net.deposit.limit.info.month.header',
                'language' => 'en',
                'value' => 'Monthly Net Deposit Limit'
            ],
            [
                'alias' => 'net.deposit.limit.info.month.body.html',
                'language' => 'en',
                'value' => "<p>Because your safety is important to us, you cannot deposit at the moment because you have
                            reached your Casino Net Deposit Limit. This limit will reset at the end of the month at
                            00:00 GMT.</p>
                            <p>If you wish to increase this limit, click the 'Request limit increase' button below and a
                             support agent will be in contact with you.</p>"
            ]
        ];
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->localized_strings_table)
            ->insert($this->localized_strings_table_items);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->localized_strings_table_items as $item) {
            $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', '=', $item['alias'])
                ->where('language', '=', $item['language'])
                ->delete();
        }
    }
}
