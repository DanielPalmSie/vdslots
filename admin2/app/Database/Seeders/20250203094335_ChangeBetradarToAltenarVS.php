<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * ./console seeder:up 20250203094335
 * ./console seeder:down 20250203094335
 */
class ChangeBetradarToAltenarVS extends Seeder
{
    private string $brand;
    private string $menus;
    private string $pages;
    private string $boxes;
    private Connection $connection;

    public function init(): void
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();

        $this->menus = 'menus';
        $this->pages = 'pages';
        $this->boxes = 'boxes';
    }

    public function up(): void
    {
        if ($this->brand !== 'videoslots') {
            return;
        }

        /** Adjust menu redirect */
        $this->connection->table($this->menus)->where('alias', '=', 'sportsbook-prematch')->update([
            'link_page_id' => $this->getAltenarPageId(),
        ]);

        /** Adjust routes to Altenar paths */
        $this->connection->table($this->pages)->where('cached_path', '=', '/sports')->update([
            'cached_path' => '/sports/#/overview'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports')->update([
            'cached_path' => '/mobile/sports/#/overview'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports/live')->update([
            'cached_path' => '/mobile/sports/#/live'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/sports/live')->update([
            'cached_path' => '/sports/#/live'
        ]);

        /** Update boxes for Sportsbook pages */
        $this->connection->table($this->boxes)->where('box_class', '=', 'SportsbookBox')->update([
            'box_class' => 'AltenarBox'
        ]);

        /* Remove Betradar prematch page **/
        $this->connection->table($this->pages)->where('cached_path', '=', '/sports/prematch')->delete();

        /* Remove Betradar prematch box **/
        $this->connection->table($this->boxes)->where('page_id', '=', 901)->delete();
    }

    public function down(): void
    {
        if ($this->brand !== 'videoslots') {
            return;
        }

        /** Insert Betradar prematch page */
        $this->connection->table($this->pages)->insert([
            'page_id' => 901,
            'parent_id' => 898,
            'alias' => 'prematch',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/sports/prematch'
        ]);

        /* Insert Betradar prematch box **/
        $this->connection->table($this->boxes)->insert([
            'box_id' => 1601,
            'container' => 'full',
            'box_class' => 'SportsbookBox',
            'priority' => 0,
            'page_id' => $this->getBetradarPageId()
        ]);

        /** Adjust menu redirect */
        $this->connection->table($this->menus)->where('alias', '=', 'sportsbook-prematch')->update([
            'link_page_id' => $this->getBetradarPageId(),
        ]);

        /** Adjust routes to Betradar paths */
        $this->connection->table($this->pages)->where('cached_path', '=', '/sports/#/overview')->update([
            'cached_path' => '/sports'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports/#/overview')->update([
            'cached_path' => '/mobile/sports'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports/#/live')->update([
            'cached_path' => '/mobile/sports/live'
        ]);

        $this->connection->table($this->pages)->where('cached_path', '=', '/sports/#/live')->update([
            'cached_path' => '/sports/live'
        ]);

        /** Update boxes for Altenar pages */
        $this->connection->table($this->boxes)->where('box_class', '=', 'AltenarBox')->update([
            'box_class' => 'SportsbookBox'
        ]);
    }

    private function getAltenarPageId(): int
    {
        $page = $this->connection->table($this->pages)->where('cached_path', '=', '/sports')->first();

        return (int) $page->page_id;
    }

    private function getBetradarPageId(): int
    {
        $page = $this->connection->table($this->pages)->where('cached_path', '=', '/sports/prematch')->first();

        return (int) $page->page_id;
    }
}