<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateSimpleExpandableBoxAttributesForBlockedCountries extends Migration
{
    /** @var string */
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('box_id', 944)
            ->where('attribute_name', 'box_class')
            ->update(['attribute_value' => 'frame-block fb-background frame-block-game-popup']);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('box_id', 944)
            ->where('attribute_name', 'box_class')
            ->update(['attribute_value' => 'frame-block fb-background']);
    }
}
