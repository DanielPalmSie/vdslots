<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class Update29DayBonusTemplateConfigType extends Migration
{
    protected $newConfigType;
    protected $oldConfigType;
    protected $table;

    public function init()
    {
        $this->table = 'config';

        $this->newConfigType = '{"type":"template", "delimiter":"::", "next_data_delimiter":"\n", "format":"<:Name><delimiter><:Value>"}';
        $this->oldConfigType = '{"type":"template", "delimiter":"::", "next_data_delimiter":"
", "format":"<:Name><delimiter><:Value>"}';
          
    }

    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function ($connection) {

            for ($x = 1; $x <= 15; $x++) {
                $connection->table($this->table)
                    ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                        ->where('config_tag', 'bonus-templates')
                        ->update(['config_type' => $this->newConfigType]);
            }
        }, true);
     
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {

            for ($x = 1; $x <= 15; $x++) {
                $connection->table($this->table)
                    ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                        ->where('config_tag', 'bonus-templates')
                        ->update(['config_type' => $this->oldConfigType]);
            }
        }, true);
    }
}
