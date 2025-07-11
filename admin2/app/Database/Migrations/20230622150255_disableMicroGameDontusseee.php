<?php

use Phpmig\Migration\Migration;
use \App\Extensions\Database\FManager as DB;
class DisableMicroGameDontusseee extends Migration
{
    private string $table = 'micro_games';
    private int $game_id = 39382972;

    /**
     * @var App\Extensions\Database\Connection\Connection[]
     */
    private array $connections = [];

    public function init()
    {
        $nodes = DB::getConnectionsList();
        $masterConnection = DB::getMasterConnection();
        $this->connections = $nodes;
        $this->connections[] = $masterConnection;

    }
    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->connections as $connection) {
            $connection
                ->table($this->table)
                ->where('id', '=', $this->game_id)
                ->update(['enabled' => 0]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->connections as $connection) {
            $connection
                ->table($this->table)
                ->where('id', '=', $this->game_id)
                ->update(['enabled' => 1]);
        }
    }
}
