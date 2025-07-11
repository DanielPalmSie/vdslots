<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RemoveOverLinkFreeSpinForSweden extends Migration
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
            ->where('box_id', '=', '1210')
            ->where('attribute_name', '=', 'overlay_link_freespins_SE')
            ->delete();
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)->insert([
                'box_id' => '1210',
                'attribute_name' => 'overlay_link_freespins_SE',
                'attribute_value' => '/the-wheel-of-jackpots-info/'
            ]);
    }
}