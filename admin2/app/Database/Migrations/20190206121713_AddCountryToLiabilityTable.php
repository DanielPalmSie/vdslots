<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddCountryToLiabilityTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'users_monthly_liability';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'country')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('country', 3)->after('currency')->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'country')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn('country');
            });
        }
    }
}
