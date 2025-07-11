<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddKungaslottetLandingPageLogo extends Migration
{

    protected string $tablePages;
    private string $tablePageSetting;
    private array $pageSettingsData;

    private Connection $connection;

    public function init()
    {

        $this->connection = DB::getMasterConnection();

        $this->tablePages = 'pages';
        $this->tablePageSetting = 'page_settings';

        $this->pageSettingsData = [
            '/landing' => [
                    'name' => 'landing_logo',
                    'value' => 'Landing-page-logo.png',
                    'old_value' => 'Landing-page-logo.jpg',
            ],
            '/mobile/landing' => [
                    'name' => 'landing_logo',
                    'value' => 'Landing-page-mobile-logo.png',
                    'old_value' => 'Landing-page-mobile-logo.jpg',
            ]
        ];
    }


    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->pageSettingsData as $cachedPath => $pageSettings) {

            $page_id = $this->getPageID($cachedPath);
            if(!empty($page_id)) {
              $this->connection->table($this->tablePageSetting)
                        ->where('page_id', $page_id)
                        ->where('name', $pageSettings['name'])
                        ->update(['value' => $pageSettings['value']]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->pageSettingsData as $cachedPath => $pageSettings) {

            $page_id = $this->getPageID($cachedPath);
            if(!empty($page_id)) {
                $this->connection->table($this->tablePageSetting)
                    ->where('page_id', $page_id)
                    ->where('name', $pageSettings['name'])
                    ->update(['value' => $pageSettings['old_value']]);
            }
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
