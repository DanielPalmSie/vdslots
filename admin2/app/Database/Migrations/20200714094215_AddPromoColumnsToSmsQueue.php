<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class AddPromoColumnsToSmsQueue extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'sms_queue';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'priority')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->tinyInteger('priority')->before('messaging_campaign_id')->default(0)->index();
            });
        }
        if (!$this->schema->hasColumn($this->table, 'scheduled_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->timestamp('scheduled_at')
                    ->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))
                    ->after('created_at')
                    ->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'scheduled_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('scheduled_at');
            });
        }
        if ($this->schema->hasColumn($this->table, 'priority')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('priority');
            });
        }
    }
}
