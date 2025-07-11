<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddNgrToTriggers extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'triggers';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'ngr_threshold')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->bigInteger('ngr_threshold')->default(0);
            });
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'ngr_threshold')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->dropColumn('ngr_threshold');
            });
        }
    }
}
