<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnDesktopOrMobileToTournamentTpls extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'tournament_tpls';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'desktop_or_mobile')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->string('desktop_or_mobile', 50)->default('desktop');
            });
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'desktop_or_mobile')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->dropColumn('desktop_or_mobile');
            });
        }
    }
}
