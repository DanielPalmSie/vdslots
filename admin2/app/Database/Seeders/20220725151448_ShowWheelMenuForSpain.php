<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class ShowWheelMenuForSpain extends Seeder
{

    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $menus = $this->connection
            ->table($this->table)
            ->select()
            ->whereIn('menu_id', [375, 376])
            ->get();

        foreach ($menus as $menu) {
            $countries = explode(' ', $menu->excluded_countries);
            $countries = array_filter($countries, function($country){
                return $country != 'ES';
            });
            $menu->excluded_countries = implode(' ', $countries);
            $this->connection
                ->table($this->table)
                ->where(['menu_id' => $menu->menu_id])
                ->update(['excluded_countries' => $menu->excluded_countries]);
        }
    }

    public function down()
    {
        $menus = $this->connection
            ->table($this->table)
            ->select()
            ->whereIn('menu_id', [375, 376])
            ->get();

        foreach ($menus as $menu) {
            $countries = explode(' ', $menu->excluded_countries);
            if(in_array('ES', $countries)) {
                continue;
            }
            $countries[] = 'ES';
            $menu->excluded_countries = implode(' ', $countries);
            $this->connection
                ->table($this->table)
                ->where(['menu_id' => $menu->menu_id])
                ->update(['excluded_countries' => $menu->excluded_countries]);
        }
    }
}