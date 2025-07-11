<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddJurisdictionColumnAndUpdateIndexInJpLogTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'jp_log';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('jurisdiction', 10)->after('jp_name');
        });

        try {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropIndex('jp_id');
            });
        } catch (\Exception $e) {
            // No action required if index does not exist
        }

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unique(['jp_id', 'created_at', 'currency', 'jurisdiction'], 'jp_id');
        });
    }

    public function down()
    {
        try {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropIndex('jp_id');
            });
        } catch (\Exception $e) {
            // No action required if index does not exist
        }

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('jurisdiction');
        });

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unique(['jp_id', 'created_at', 'currency'], 'jp_id');
        });
    }
}
