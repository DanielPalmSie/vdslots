<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddIndexToTournamentsDesktopOrMobile extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'tournaments';
        $this->schema = $this->get('schema');
    }
    
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->index('desktop_or_mobile', 'desktop_or_mobile');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropIndex('desktop_or_mobile', 'desktop_or_mobile');
        });
    }
}
