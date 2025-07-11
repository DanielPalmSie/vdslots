<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;

class UpdateRegIpInUsersTable extends Migration
{
    private const IP_LENGTH_LONG = 55;
    private const IP_LENGTH_SHORT = 20;

    private string $table;
    private MysqlBuilder $schema;

    public function init()
    {
        $this->table = 'users';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('reg_ip', self::IP_LENGTH_LONG)->change();
                $table->string('cur_ip', self::IP_LENGTH_LONG)->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('reg_ip', self::IP_LENGTH_SHORT)->change();
                $table->string('cur_ip', self::IP_LENGTH_SHORT)->change();
            });
        }
    }
}
