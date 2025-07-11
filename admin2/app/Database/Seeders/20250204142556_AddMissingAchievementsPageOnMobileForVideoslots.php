<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddMissingAchievementsPageOnMobileForVideoslots extends Seeder
{
    private Connection $connection;

    private string $pages_table = "pages";
    private string $menu_table = "menus";

    private string $menu_name = "#mobile.menu.achievements";
    private string $page_alias = "achievements";
    private string $page_path = "/mobile/achievements";
    private string $filename = "diamondbet/mobile.php";

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        $pageId = $this->connection->table($this->menu_table)
            ->where('name', $this->menu_name)
            ->value('link_page_id');

        if ($this->brand === 'videoslots') {
            // Check if page already exists
            $existingPage = $this->connection->table($this->pages_table)
                ->where('page_id', $pageId)
                ->first();

            if (!$existingPage) {
            $this->connection->table($this->pages_table)->insert([
                'page_id' => $pageId,
                'alias' => $this->page_alias,
                'parent_id' => $this->getMobilePageParentID(),
                'filename' => $this->filename,
                'cached_path' => $this->page_path
            ]);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'videoslots') {
            $this->connection->table($this->pages_table)
                ->where('alias', $this->page_alias)
                ->delete();
        }
    }

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->pages_table)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', $this->filename)
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }
}