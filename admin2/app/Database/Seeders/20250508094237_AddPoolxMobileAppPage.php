<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddPoolxMobileAppPage extends Seeder
{
    private string $pages;
    private string $boxes;
    private Connection $connection;

    public function init(): void
    {
        $this->pages = 'pages';
        $this->boxes = 'boxes';
        $this->connection = DB::getMasterConnection();
    }

    public function up(): void
    {
        $this->connection->table($this->pages)->insert([
            'parent_id' => 0,
            'alias' => 'poolx-mobile-app',
            'filename' => 'diamondbet/mobile_clean.php',
            'cached_path' => '/poolx-mobile-app'
        ]);

        $this->connection->table($this->boxes)->insert([
            'container' => 'full',
            'box_class' => 'PoolXMobileAppBox',
            'priority' => 0,
            'page_id' => $this->getMobileAppPageId()
        ]);
    }

    public function down(): void
    {
        $this->connection->table($this->boxes)->where('box_class', '=', 'PoolXMobileAppBox')->delete();

        $this->connection->table($this->pages)->where('alias', '=', 'poolx-mobile-app')->delete();
    }

    private function getMobileAppPageId(): int
    {
        $page = $this->connection
            ->table($this->pages)
            ->where('alias', '=', 'poolx-mobile-app')
            ->first();

        return (int)$page->page_id;
    }
}