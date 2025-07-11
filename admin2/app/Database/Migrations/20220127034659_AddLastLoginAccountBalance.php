<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddLastLoginAccountBalance extends Migration
{
    private $table;

    private array $items = [
        [
            'alias'    => 'user.h3',
            'language' => 'en',
            'value'    => 'Account balance on last login'
        ],
        [
            'alias'    => 'casino.last.login.balance.upc',
            'language' => 'en',
            'value'    => 'My Balances'
        ],
        [
            'alias'    => 'casino.last.login.last.balance.upc',
            'language' => 'en',
            'value'    => 'Last Login:'
        ],
        [
            'alias'    => 'casino.last.login.current.balance.upc',
            'language' => 'en',
            'value'    => 'Current:'
        ]
    ];

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->table = 'localized_strings';

        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->items as $item) {
            $exists = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            DB::getMasterConnection()
                ->table($this->table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->items as $translation) {
            $this->connection
                ->table($this->table)
                ->where('alias', $translation['alias'])
                ->delete();
        }
    }
}