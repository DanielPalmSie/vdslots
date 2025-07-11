<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddLocaleStringForBonusExpired extends Migration
{
    private string $table;
    private array $data;
    private $schema;
    private $brand;

    /**
     * initial function
     * @return void
     */
    public function init()
    {
        $this->brand = phive('BrandedConfig');
        $this->schema = $this->get('schema');
        $this->table = 'localized_strings';
        $this->data = [
            [
                'alias'    => 'bonus.status.expired',
                'language' => 'en',
                'value'    => 'Expired'
            ],
        ];
    }


    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        DB::getMasterConnection()
            ->table($this->table)
            ->insert($this->data);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand->getBrand() !== $this->brand::BRAND_MEGARICHES) {
            return;
        }

        DB::getMasterConnection()
            ->table($this->table)
            ->whereIn('alias', array_column($this->data, 'alias'))
            ->where('language', '=', 'en')
            ->delete();
    }
}
