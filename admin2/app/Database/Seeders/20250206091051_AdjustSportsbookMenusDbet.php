<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * ./console seeder:up 20250206091051
 * ./console seeder:down 20250206091051
 */
class AdjustSportsbookMenusDbet extends Seeder
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
        if ($this->brand !== 'dbet') {
            return;
        }

        /** Adjust desktop page */
        $desktopPage = $this->connection->table($this->pages)->where('cached_path', '=', '/sportsbook')->first();
        if ($desktopPage) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', $desktopPage->parent_id)
                ->where('alias', 'sports')
                ->where('page_id', '!=', $desktopPage->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', $desktopPage->page_id)->update([
                    'cached_path' => '/sports/#/overview',
                    'alias' => 'sports'
                ]);
            }
        }

        /** Adjust mobile page */
        $mobilePage = $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sportsbook')->first();
        if ($mobilePage) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', $mobilePage->parent_id)
                ->where('alias', 'sports')
                ->where('page_id', '!=', $mobilePage->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', $mobilePage->page_id)->update([
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
        if ($this->brand !== 'dbet') {
            return;
        }

        /** Adjust desktop page */
        $desktopPage = $this->connection->table($this->pages)->where('cached_path', '=', '/sports/#/overview')->first();
        if ($desktopPage) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', $desktopPage->parent_id)
                ->where('alias', 'sportsbook')
                ->where('page_id', '!=', $desktopPage->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', $desktopPage->page_id)->update([
                    'cached_path' => '/sportsbook',
                    'alias' => 'sportsbook'
                ]);
            }
        }

        /** Adjust mobile page */
        $mobilePage = $this->connection->table($this->pages)->where('cached_path', '=', '/mobile/sports/#/overview')->first();
        if ($mobilePage) {
            $conflict = $this->connection->table($this->pages)
                ->where('parent_id', $mobilePage->parent_id)
                ->where('alias', 'sportsbook')
                ->where('page_id', '!=', $mobilePage->page_id)
                ->exists();

            if (!$conflict) {
                $this->connection->table($this->pages)->where('page_id', $mobilePage->page_id)->update([
                    'cached_path' => '/mobile/sportsbook',
                    'alias' => 'sportsbook'
                ]);
            }
        }

        /** Adjust desktop menu */
        $this->connection->table($this->menus)->where('alias', '=', 'sportsbook-live')->update([
            'link' => '/sportsbook/#/live'
        ]);

        /** Adjust mobile menu */
        $this->connection->table($this->menus)->where('name', '=', 'menu.secondary.sportsbook-live')->update([
            'link' => '/mobile/sportsbook/#/live'
        ]);
    }
}