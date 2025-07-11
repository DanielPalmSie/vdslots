<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class UpdateStatusEnumInBonusEntries extends Migration
{
    protected string $table;
    protected $schema;
    private $brand;

    /**
     * initial function
     * @return void
     */
    public function init()
    {
        $this->brand = phive('BrandedConfig');
        $this->table = 'bonus_entries';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
            $connection->statement("ALTER TABLE bonus_entries MODIFY COLUMN status enum('pending','active','approved','completed','failed','expired') CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'pending' NOT NULL;");
        }, true);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
            $connection->statement("ALTER TABLE bonus_entries MODIFY COLUMN status enum('pending','active','approved','completed','failed') CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'pending' NOT NULL;");
        }, true);
    }
}
