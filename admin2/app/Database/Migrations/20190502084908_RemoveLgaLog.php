<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class RemoveLgaLog extends Migration
{    
    protected $schema;

    public function init()
    {        
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->drop('lga_log');       
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        
    }
}
