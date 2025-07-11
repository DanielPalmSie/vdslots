<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddIndexFinishedAtToTrophyAwardOwnership extends Migration
{
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;

    public function init(): void
    {
        $this->table = 'trophy_award_ownership';
        $this->schema = $this->get('schema');
    }


    public function up(): void
    {
        try {
            $this->schema->table($this->table, function (Blueprint $table) {

                $table->asSharded();

                $table->index('finished_at', 'idx_finished_at');
            });
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function down(): void
    {
        try {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();

                $table->dropIndex('idx_finished_at');
            });
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
