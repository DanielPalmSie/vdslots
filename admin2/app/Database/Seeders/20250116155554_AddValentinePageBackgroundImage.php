<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddValentinePageBackgroundImage extends Seeder
{
    private string $tablePages;
    private string $tablePageSettings;
    private Connection $connection;

    protected string $cachedPath;
    protected string $pageAlias;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->cachedPath = '/valentines';
        $this->pageAlias = 'valentines';
    }

    public function up()
    {
        $pageId = $this->getPageID($this->cachedPath);

        if (!$pageId) {
            return;
        }

        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $pageId)
            ->where('name', '=', 'landing_bkg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $pageId,
                'name' => 'landing_bkg',
                'value' => 'valentines2025_bg.jpg'
            ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $pageId = $this->getPageID($this->cachedPath);

        if (!$pageId) {
            return;
        }

        $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $pageId)
            ->where('name', '=', 'landing_bkg')
            ->delete();
    }

    private function getPageID(string $cache_path): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', $this->pageAlias)
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }
}
