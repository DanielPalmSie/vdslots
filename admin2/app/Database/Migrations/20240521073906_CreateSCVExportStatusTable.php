<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateSCVExportStatusTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'scv_export_status';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unique();
            $table->string('status');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at');
            $table->index('user_id', 'user_idx');
            $table->index('status', 'statusx');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
