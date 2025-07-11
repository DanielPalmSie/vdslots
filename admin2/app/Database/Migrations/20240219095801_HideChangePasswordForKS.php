<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class HideChangePasswordForKS extends Migration
{

    public function init()
    {
        $this->table = 'menus';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();


    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {

            $this->connection
                ->table($this->table)
                ->where('menu_id', '=',192)
                ->where('link_page_id', '=', 97)
                ->update(['excluded_countries' => 'SE']);

        }


    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $menus = $this->connection
                ->table($this->table)
                ->select()
                ->where('menu_id', '=',192)
                ->get();

                $this->connection
                    ->table($this->table)
                    ->where(['menu_id' => $menus->menu_id])
                    ->update(['excluded_countries' => null]);


        }



    }

}
