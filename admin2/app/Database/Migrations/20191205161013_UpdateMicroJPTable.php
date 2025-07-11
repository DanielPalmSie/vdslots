<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdateMicroJPTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'micro_jps';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('game_id', 255)->after('id')->index();
            $table->string('jurisdiction', 10)->after('game_id')->default('DEFAULT')->index(); // country ISO or "DEFAULT"
            $table->tinyInteger('tmp')->default('1')->index();
            $table->dropUnique('jp_id');
            $table->index('jp_id', 'jp_id_index');
            $table->index('currency', 'currency_index');
            $table->index('network', 'network_index');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropColumn('game_id');
            $table->dropColumn('jurisdiction');
            $table->dropColumn('tmp');
            $table->dropIndex('jp_id_index');
            $table->dropIndex('currency_index');
            $table->dropIndex('network_index');
            $table->unique(['jp_id', 'currency'], 'jp_id');
        });
    }
}
