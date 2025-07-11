<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class UpdateAnalyticsTableAddGTMEnabled extends Migration
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
        if ($this->schema->hasTable($this->table)) {
            if ($this->schema->hasColumn($this->table, 'ga_cookie_id')) {
                $this->schema->table($this->table, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                        $connection->statement("ALTER TABLE `{$this->table}` ADD is_gtm_enabled BOOL DEFAULT 1 NULL COMMENT 'Is GTM is enabled?' AFTER ga_cookie_id");
                        $connection->statement("ALTER TABLE `{$this->table}` ADD is_gtm_blocked BOOL DEFAULT 0 NULL COMMENT 'Is GTM is blocked by tool?' AFTER is_gtm_enabled");
                    }, true);
                });
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                    $connection->statement("ALTER TABLE `{$this->table}` DROP COLUMN is_gtm_enabled");
                    $connection->statement("ALTER TABLE `{$this->table}` DROP COLUMN is_gtm_blocked");
                }, true);
            });
        }
    }
}
