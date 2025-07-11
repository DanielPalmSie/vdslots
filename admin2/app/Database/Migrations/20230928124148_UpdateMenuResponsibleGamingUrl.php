<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateMenuResponsibleGamingUrl extends Migration
{
    private Connection $connection;
    private string $table;
    private string $oldValue;
    private string $newValue;

    public function init()
    {
        $this->table = 'pages';
        $this->connection = DB::getMasterConnection();
        $this->oldValue = 'responsible-gambling';
        $this->newValue = 'responsible-gaming';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
                ->table($this->table)
                ->where('page_id', 447)
                ->update([
                    'alias' => $this->newValue,
                    'cached_path' => '/mobile/'.$this->newValue
                ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
                ->table($this->table)
                ->where('page_id', 447)
                ->update([
                    'alias' => $this->oldValue,
                    'cached_path' => '/mobile/'.$this->oldValue
                ]);

    }
}
