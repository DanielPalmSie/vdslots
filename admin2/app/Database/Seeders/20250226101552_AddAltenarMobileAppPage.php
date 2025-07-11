<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

/**
 * ./console seeder:up 20250226101552
 *
 * ./console seeder:down 20250226101552
 */
class AddAltenarMobileAppPage extends Seeder
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
            'alias' => 'sports-mobile-app',
            'filename' => 'diamondbet/mobile_clean.php',
            'cached_path' => '/sports-mobile-app/#/overview'
        ]);

        $this->connection->table($this->boxes)->insert([
            'container' => 'full',
            'box_class' => 'AltenarMobileAppBox',
            'priority' => 0,
            'page_id' => $this->getMobileAppPageId()
        ]);
    }

    public function down(): void
    {
        $this->connection->table($this->boxes)->where('box_class', '=', 'AltenarMobileAppBox')->delete();

        $this->connection->table($this->pages)->where('alias', '=', 'sports-mobile-app')->delete();
    }

    private function getMobileAppPageId(): int
    {
        $page = $this->connection
            ->table($this->pages)
            ->where('alias', '=', 'sports-mobile-app')
            ->first();

        return (int)$page->page_id;
    }
}