<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use Illuminate\Database\Schema\Blueprint;

class AddBtagInAnalyticsTable extends Migration
{
    private string $brand;
    private string $table;
    private Connection $connection;

    public function init(): void
    {
        $this->table = 'analytics';
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasTable($this->table)) {
            if ($this->schema->hasColumn($this->table, 'is_gtm_blocked')) {
                $this->schema->table($this->table, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                        $connection->statement("ALTER TABLE `{$this->table}` DROP COLUMN IF EXISTS btag");
                        $connection->statement("ALTER TABLE `{$this->table}` ADD btag varchar(100) NULL COMMENT 'btag from RavenTrack' AFTER is_gtm_blocked");
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
                    $connection->statement("ALTER TABLE `{$this->table}` DROP COLUMN btag");
                }, true);
            });
        }
    }
}
