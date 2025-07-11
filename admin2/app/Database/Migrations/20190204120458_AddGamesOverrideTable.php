<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddGamesOverrideTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        
    }

    /**
     * Do the migration
     */
    public function up()
    {
	DB::statement("
            CREATE TABLE `game_country_overrides` (
                `id` bigint(21) NOT NULL,
                `game_id` bigint(21) NOT NULL,
                `country` varchar(2) NOT NULL,
                `ext_game_id` varchar(255) NOT NULL,
                `ext_launch_id` varchar(255) NOT NULL,
                `payout_extra_percent` float NOT NULL DEFAULT '0',
                `payout_percent` float NOT NULL DEFAULT '0.96',
                `device_type` varchar(20) NOT NULL,
                `device_type_num` tinyint(1) NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        DB::statement("
              ALTER TABLE `game_country_overrides`
                ADD PRIMARY KEY (`id`),
                ADD UNIQUE KEY `game_country_idx` (`game_id`,`country`),
                ADD KEY `ext_launch_id_idx` (`ext_launch_id`) USING BTREE,
                ADD KEY `ext_game_id_device_type_idx` (`ext_game_id`,`device_type_num`) USING BTREE");

        DB::statement("
              ALTER TABLE `game_country_overrides`
                MODIFY `id` bigint(21) NOT NULL AUTO_INCREMENT");
        
    }

    /**
     * Undo the migration
     */
    public function down()
    {
	$this->table = 'game_country_overrides';
        $this->schema = $this->get('schema');
        $this->schema->drop($this->table);        
    }
}
