<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddProvinceToUsersMonthlyLiabilityTable extends Migration
{

    protected $table;

    protected $schema;

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
        if (!$this->schema->hasColumn($this->table, 'province')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('province', 3)->nullable()->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'province')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn('province');
            });
        }
    }
}
