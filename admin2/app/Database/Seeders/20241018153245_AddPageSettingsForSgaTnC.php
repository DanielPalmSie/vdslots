<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddPageSettingsForSgaTnC extends Seeder
{

    protected string $tablePages;
    private string $tablePageSetting;
    private Connection $connection;
    private string $alias = 'sga-svenska-regler-och-villkor';
    private string $brand;


    public function init()
    {
        $this->tablePages = 'pages';
        $this->tablePageSetting = 'page_settings';
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }
        $pageList = $this->connection->table($this->tablePages)
            ->where('alias', '=', $this->alias)
            ->get('page_id');


        foreach ($pageList as $page) {

            $pageSettingExists = $this->connection->table($this->tablePageSetting)
                ->where('page_id', $page->page_id)
                ->where('name', 'landing_bkg')
                ->exists();

            if(!$pageSettingExists) {
                $this->connection->table($this->tablePageSetting)
                    ->insert([
                        'page_id' => $page->page_id,
                        'name' => 'landing_bkg',
                        'value' => 'DBET-Background3.jpg'
                    ]);
            }
        }
    }

    public function down()
    {
        if ($this->brand !== 'dbet') {
            return;
        }
        $pageList = $this->connection->table($this->tablePages)
            ->where('alias', '=', $this->alias)
            ->get('page_id');

        foreach ($pageList as $page) {
            $this->connection->table($this->tablePageSetting)
                ->where('page_id', $page->page_id)
                ->delete();
        }
    }
}
