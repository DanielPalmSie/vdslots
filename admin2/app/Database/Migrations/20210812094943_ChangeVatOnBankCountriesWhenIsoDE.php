<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class ChangeVatOnBankCountriesWhenIsoDE extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    protected $connection;

    public function init()
    {
        $this->table = 'bank_countries';
        $this->schema = $this->get('schema');
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table($this->table)->where('iso', '=', 'DE')->update(['vat' => 0]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)->where('iso', '=', 'DE')->update(['vat' => 0.19]);
    }
}
