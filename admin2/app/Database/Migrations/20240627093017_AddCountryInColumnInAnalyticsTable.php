<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Schema\Blueprint;
class AddCountryInColumnInAnalyticsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'analytics';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'country_in')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('country_in', 5)->nullable()->after('traffic_source');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'country_in')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn('country_in');
            });
        }
    }
}
