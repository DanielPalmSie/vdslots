<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddNotificationsCountryColumn extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_notifications';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'country')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('country', 5);
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
                $table->asSharded();
                $table->dropColumn('country');
            });
        }
    }
}
