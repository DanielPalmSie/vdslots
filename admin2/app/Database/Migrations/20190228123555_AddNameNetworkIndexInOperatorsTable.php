<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddNameNetworkIndexInOperatorsTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'operators';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unique(['name', 'network'], 'unique_name_network');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('unique_name_network');
        });

    }
}
