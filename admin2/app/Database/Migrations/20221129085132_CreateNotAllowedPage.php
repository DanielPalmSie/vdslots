<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateNotAllowedPage extends Migration
{
    /** @var string */
    protected $tablePages;
    protected $tableBoxes;
    protected $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->connection = DB::getMasterConnection();
    }
    /**
     * Do the migration
     */
    public function up()
    {
        // 403 forbidden country
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'forbidden-country',
            'filename' => 'diamondbet/forbidden-country.php',
            'cached_path' => 'forbidden-country',
        ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'forbidden-country')
            ->where('filename', '=', 'diamondbet/forbidden-country.php')
            ->where('cached_path', '=', 'forbidden-country')
            ->delete();
    }
}
