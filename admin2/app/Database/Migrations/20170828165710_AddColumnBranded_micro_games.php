<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnBrandedMicroGames extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'micro_games';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->tinyInteger('branded')->default(0)->after('network')->comment('0 - non branded, 1 - branded');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('branded');
        });
    }
}
