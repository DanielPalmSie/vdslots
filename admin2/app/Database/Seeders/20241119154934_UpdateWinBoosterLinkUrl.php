<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class UpdateWinBoosterLinkUrl extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $tableMenus;


    public function init()
    {
        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {

        if ($this->brand !== 'megariches') {
            return;
        }

        $isPageSettingExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'my-rainbow-reasure')
            ->exists();

        if ($isPageSettingExists) {
            $this->connection
                ->table($this->tableMenus)
                ->where('alias', '=', 'my-rainbow-reasure')
                ->update([
                    'getvariables' => '[user/]my-winbooster/'
                ]);
        }
    }

    public function down()
    {

        if ($this->brand !== 'megariches') {
            return;
        }

        $isPageSettingExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'my-rainbow-reasure')
            ->exists();

        if ($isPageSettingExists) {
            $this->connection
                ->table($this->tableMenus)
                ->where('alias', '=', 'my-rainbow-reasure')
                ->update([
                    'getvariables' => '[user/]/my-rainbow-treasure/'
                ]);
        }
    }
}
