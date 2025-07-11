<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class UpdatingCALicenseProvinceConfig extends Migration
{
    private string $table;
    private string $oldValue;
    private string $newValue;

    public function init()
    {
        $this->table = 'license_config';
        $this->oldValue = 'New Brunswich';
        $this->newValue = 'New Brunswick';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection
                ->table($this->table)
                ->where('config_name', $this->oldValue)
                ->where('config_tag', '=', 'provinces')
                ->update([
                    'config_name' => $this->newValue,
                    'config_value' => '{"iso_code":"NB","province":"New Brunswick"}'
                ]);
        }, true);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection
                ->table($this->table)
                ->where('config_name', $this->newValue)
                ->where('config_tag', '=', 'provinces')
                ->update([
                    'config_name' => $this->oldValue,
                    'config_value' => '{"iso_code":"NB","province": "New Brunswich"}'
                ]);
        }, true);
    }
}
