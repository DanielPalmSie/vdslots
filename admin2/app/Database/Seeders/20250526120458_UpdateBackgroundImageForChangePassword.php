<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateBackgroundImageForChangePassword extends Seeder
{

    protected string $tablePages;
    private string $tablePageSetting;

    private string $brand;

    private Connection $connection;

    private array $pageSettingsData = [];

    public function init()
    {
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->tablePages = 'pages';
        $this->tablePageSetting = 'page_settings';

        $this->connection = DB::getMasterConnection();

        $this->pageSettingsData = [
            [
                'page_id' => $this->getPageID('/admin/change-password'),
                'name' => 'landing_bkg',
                'value' => 'main-background.jpg',
            ]
        ];
    }

    public function up()
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->pageSettingsData as $setting) {
            $this->connection->table($this->tablePageSetting)
                ->insert($setting);
        }
    }


    public function down()
    {

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->pageSettingsData as $setting) {

            $this->connection->table($this->tablePageSetting)
                ->where('page_id', $setting['page_id'])
                ->where('name', $setting['name'])
                ->delete();
        }
    }


    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }
}
