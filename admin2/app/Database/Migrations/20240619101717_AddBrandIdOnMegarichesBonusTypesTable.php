<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Schema\Blueprint;

class AddBrandIdOnMegarichesBonusTypesTable extends Migration
{
    protected $table;
    protected $schema;

    private $brand;
    private $brandId;

    public function init()
    {
        $this->table = 'bonus_types';
        $this->schema = $this->get('schema');
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->brandId = 103;
    }


    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }
        if (!$this->schema->hasColumn($this->table, 'brand_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->unsignedTinyInteger('brand_id')->after('award_id')->default($this->brandId);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }
        if ($this->schema->hasColumn($this->table, 'brand_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('brand_id');
            });
        }
    }
}