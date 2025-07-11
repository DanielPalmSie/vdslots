<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddSportsbookBlockToPlayMessage extends Migration
{
    private $localized_strings_table;
    private $localized_strings_connection_table;
    private $alias;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->localized_strings_connection_table = 'localized_strings_connections';
        $this->alias = 'sb.betslip.play_block_message';

        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table($this->localized_strings_connection_table)->insert(
            [
                'target_alias' => $this->alias,
                'bonus_code' => 0,
                'tag' => 'sb.betslip'
            ]
        );

        $this->connection->table($this->localized_strings_table)->insert(
            [
                'alias' => $this->alias,
                'language' => 'en',
                'value' => 'Your game play functionality has been inactivated. Please contact support@videoslots.com for more information.'
            ]
        );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->localized_strings_connection_table)
            ->where('target_alias', '=', $this->alias)
            ->delete();

        $this->connection
            ->table($this->localized_strings_table)
            ->where('alias', '=', $this->alias)
            ->delete();
    }
}
