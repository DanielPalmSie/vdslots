<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * ./console seeder:up 20250206094809
 * ./console seeder:down 20250206094809
 */
class AdjustSportsbookMenusKunga extends Seeder
{
    private string $brand;
    private string $menus;
    private string $pages;
    private Connection $connection;

    public function init(): void
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();

        $this->menus = 'menus';
        $this->pages = 'pages';
    }

    public function up(): void
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        /** Remove unused desktop page */
        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports')->delete();

        /** Adjust desktop page */
        $page = $this->connection->table($this->pages)->where('cached_path', '=', '/sportsbook')->first();
        if ($page) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', '=', $page->parent_id)
                ->where('alias', '=', 'sports')
                ->where('page_id', '!=', $page->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', '=', $page->page_id)->update([
                    'cached_path' => '/sports/#/overview',
                    'alias' => 'sports'
                ]);
            }
        }

        /** Adjust mobile page */
        $mobilePage = $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sportsbook')->first();
        if ($mobilePage) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', '=', $mobilePage->parent_id)
                ->where('alias', '=', 'sports')
                ->where('page_id', '!=', $mobilePage->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', '=', $mobilePage->page_id)->update([
                    'cached_path' => '/mobile/sports/#/overview',
                    'alias' => 'sports'
                ]);
            }
        }

        /** Adjust desktop menu */
        $this->connection->table($this->menus)->where('alias', '=', 'sportsbook-live')->update([
            'link' => '/sports/#/live'
        ]);

        /** Adjust mobile menu */
        $this->connection->table($this->menus)->where('name', '=', 'menu.secondary.sportsbook-live')->update([
            'link' => '/mobile/sports/#/live'
        ]);
    }

    public function down(): void
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        /** Adjust desktop page */
        $this->connection->table($this->pages)->where('cached_path', '=', '/sports/#/overview')->update([
            'cached_path' => '/sportsbook',
            'alias' => 'sportsbook'
        ]);

        /** Adjust mobile page */
        $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports/#/overview')->update([
            'cached_path' => '/mobile/sportsbook',
            'alias' => 'sportsbook'
        ]);

        /** Insert deleted desktop page */
        $this->connection->table($this->pages)->insert([
            'page_id' => 869,
            'parent_id' => 268,
            'alias' => 'sports',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/sports',
        ]);

        /** Adjust desktop menu */
        $this->connection->table($this->menus)->where('alias', '=', 'sportsbook-live')->update([
            'link' => ''
        ]);

        /** Adjust mobile menu */
        $this->connection->table($this->menus)->where('name', '=', 'menu.secondary.sportsbook-live')->update([
            'link' => ''
        ]);
    }
}