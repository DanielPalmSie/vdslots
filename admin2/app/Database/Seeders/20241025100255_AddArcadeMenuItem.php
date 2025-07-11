<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddArcadeMenuItem extends Seeder
{
    private Connection $connection;

    private string $brand;

    private string $menusTable;

    private string $pagesTable;

    private string $localizedStringsTable;

    public function init()
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();
        $this->menusTable = 'menus';
        $this->pagesTable = 'pages';
        $this->localizedStringsTable = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $parentMenuId = $this->connection
            ->table($this->menusTable)
            ->where('alias', 'sub-top')
            ->pluck('menu_id')
            ->first();

        $linkPageId = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/arcade')
            ->pluck('page_id')
            ->first();

        if (!$linkPageId) {
            $linkPageId = $this->connection
                ->table($this->pagesTable)
                ->insertGetId([
                    'parent_id' => 0,
                    'alias' => 'arcade',
                    'filename' => 'diamondbet/generic.php',
                    'cached_path' => '/arcade'
                ]);
        }

        $this->connection
            ->table($this->menusTable)
            ->insert([
                'parent_id' => $parentMenuId,
                'alias' => 'arcade',
                'name' => '#arcade',
                'priority' => 429,
                'link_page_id' => $linkPageId,
            ]);

        foreach (['en', 'sv'] as $lang) {
            $this->connection
                ->table($this->localizedStringsTable)
                ->upsert(
                    [
                        'alias' => 'arcade',
                        'language' => $lang,
                        'value' => 'Arcade'
                    ],
                    ['alias', 'language']
                );
        }
    }

    public function down()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $parentMenuId = $this->connection
            ->table($this->menusTable)
            ->where('alias', 'sub-top')
            ->pluck('menu_id')
            ->first();

        $this->connection
            ->table($this->menusTable)
            ->where('parent_id', $parentMenuId)
            ->where('alias', 'arcade')
            ->delete();

        $this->connection
            ->table($this->localizedStringsTable)
            ->where('alias', 'arcade')
            ->delete();

        $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/arcade')
            ->delete();
    }
}
