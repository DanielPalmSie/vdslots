<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

/**
 * Class CreateProvinceTable
 */
class CreateLicenseConfigTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;


    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'license_config';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->bigIncrements('id');
            $table->string('license', 2);
            $table->string('config_name', 255);
            $table->string('config_tag', 255);
            $table->text('config_value');
            $table->string('config_type', 255);
            $table->index(['license', 'config_name', 'config_tag'], 'license_config_idx');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('license_config_idx');
        });
        $this->schema->drop($this->table);
    }
}
