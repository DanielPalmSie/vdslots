<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Schema\Blueprint;


class AddCreatedAtToAttachmentsTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'attachments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'created_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'created_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('created_at');
            });
        }
    }
}
