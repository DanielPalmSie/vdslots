<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class RemoveSpelifieringMenuFromMobile extends Migration
{
    private Connection $connection;
    private string $boxesAttributes;
    private string $brand;

    public function init() {
        $this->boxesAttributes = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }
    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $menuValue = $this->connection
            ->table($this->boxesAttributes)
            ->where('box_id', '937')
            ->where('attribute_name', 'menues')
            ->value('attribute_value');

        $menus = explode(',', $menuValue);
        $updatedMenus = array_filter($menus, function($menu) {
            return $menu !== 'mobile-profile-menu';
        });

        $this->connection
            ->table($this->boxesAttributes)
            ->where('box_id', '937')
            ->where('attribute_name', 'menues')
            ->update(['attribute_value' => implode(',', $updatedMenus)]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $menuValue = $this->connection
            ->table($this->boxesAttributes)
            ->where('box_id', '937')
            ->where('attribute_name', 'menues')
            ->value('attribute_value');

        $menus = explode(',', $menuValue);
        array_unshift($menus, 'mobile-profile-menu');

        $this->connection
            ->table($this->boxesAttributes)
            ->where('box_id', '937')
            ->where('attribute_name', 'menues')
            ->update(['attribute_value' => implode(',', $menus)]);
    }
}
