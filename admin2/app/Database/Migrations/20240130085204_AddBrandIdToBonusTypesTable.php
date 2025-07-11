<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Schema\Blueprint;
class AddBrandIdToBonusTypesTable extends Migration
{
    protected $table;

    protected $schema;

    protected $brandId;

    private function getBrandId() {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            $this->brandId = 100;
        } else if (getenv('APP_SHORT_NAME') === 'MV') {
            $this->brandId = 101;
        } else if (getenv('APP_SHORT_NAME') === 'KS') {
            $this->brandId = 102;
        }
    }

    public function init()
    {
        $this->table = 'bonus_types';
        $this->schema = $this->get('schema');
        $this->getBrandId();
    }

    /**
     * Do the migration
     */
    public function up()
    {
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
        if ($this->schema->hasColumn($this->table, 'brand_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('brand_id');
            });
        }
    }
}
