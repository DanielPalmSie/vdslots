<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AdjustSupertipsetMenus extends Seeder
{
    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        /** Standard page */
        $this->connection->table('pages')->where('cached_path', '=', '/superstip')->update([
            'cached_path' => '/super',
            'alias' => 'super',
        ]);

        /** Mobile page */
        $this->connection->table('pages')->where('cached_path', '=', '/mobile/superstip')->update([
            'cached_path' => '/mobile/super',
            'alias' => 'super',
        ]);

        /** Redirects */
        $this->connection->table('start_go')->where('from', '=', '/superstip')->update([
            'from' => '/super',
            'to' => '/mobile/super'
        ]);

        /** Background image - standard page */
        $this->connection->table('page_settings')->insert([
            'page_id' => $this->getSupertipsetStandardPageId(),
            'name' => 'landing_bkg',
            'value' => 'supertipset_bg.jpg',
        ]);

        /** Background image - mobile page */
        $this->connection->table('page_settings')->insert([
            'page_id' => $this->getSupertipsetMobilePageId(),
            'name' => 'landing_bkg',
            'value' => 'supertipset_bg.jpg',
        ]);

        /** Menu icon - standard page */
        $this->connection->table('menus')->where('alias', '=', 'poolx')->update([
            'icon' => 'icon-supertipset',
        ]);

        /** Menu icon - mobile page */
        $this->connection->table('menus')->where('alias', '=', 'mobile-secondary-top-menu-poolx')->update([
            'icon' => 'icon-supertipset',
        ]);

        /** Menu translations */
        $this->connection->table('localized_strings')->where('alias', '=', 'menu.secondary.poolx')->update([
            'value' => 'Supertipset',
        ]);
    }

    public function down()
    {
        /** Standard page */
        $this->connection->table('pages')->where('cached_path', '=', '/super')->update([
            'cached_path' => '/superstip',
            'alias' => 'superstip',
        ]);

        /** Mobile page */
        $this->connection->table('pages')->where('cached_path', '=', '/mobile/super')->update([
            'cached_path' => '/mobile/superstip',
            'alias' => 'superstip',
        ]);

        /** Redirects */
        $this->connection->table('start_go')->where('from', '=', '/super')->update([
            'from' => '/superstip',
            'to' => '/mobile/superstip'
        ]);

        /** Background images */
        $this->connection->table('page_settings')
            ->where('name', '=', 'landing_bkg')
            ->where('value', '=', 'supertipset_bg.jpg')->delete();

        /** Menu icon - standard page */
        $this->connection->table('menus')->where('alias', '=', 'poolx')->update([
            'icon' => 'icon-vs-sportsbook',
        ]);

        /** Menu icon - mobile page */
        $this->connection->table('menus')->where('alias', '=', 'mobile-secondary-top-menu-poolx')->update([
            'icon' => 'icon-vs-sportsbook',
        ]);

        /** Menu translations */
        $this->connection->table('localized_strings')->where('alias', '=', 'menu.secondary.poolx')->update([
            'value' => 'Superstip',
        ]);
    }

    private function getSupertipsetStandardPageId(): int
    {
        $page = $this->connection
            ->table('pages')
            ->where('cached_path', '=', '/super')
            ->first();

        return (int) $page->page_id;
    }

    private function getSupertipsetMobilePageId(): int
    {
        $page = $this->connection
            ->table('pages')
            ->where('cached_path', '=', '/mobile/super')
            ->first();

        return (int) $page->page_id;
    }
}