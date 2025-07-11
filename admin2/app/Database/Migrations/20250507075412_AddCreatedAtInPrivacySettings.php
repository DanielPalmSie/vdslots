<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\FManager as DB;

class AddCreatedAtInPrivacySettings extends Migration
{
    protected string $table;
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->table = 'users_privacy_settings';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dateTime('created_at')->after('opt_in');
            });

            DB::loopNodes(function ($connection) {
                $connection->statement("ALTER TABLE {$this->table} MODIFY created_at timestamp default CURRENT_TIMESTAMP not null");
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table) &&
            $this->schema->hasColumn($this->table, 'created_at')
        ) {
            $this->schema->dropColumns($this->table, ['created_at']);
        }
    }
}
