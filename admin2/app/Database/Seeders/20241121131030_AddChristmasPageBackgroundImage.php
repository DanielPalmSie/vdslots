<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddChristmasPageBackgroundImage extends Seeder
{

    private string $tablePages;
    private string $tablePageSettings;
    private Connection $connection;
    protected string $cachedPath;
    protected string $pageAlias;
    private string $brand;


    public function init()
    {
        $this->tablePages = 'pages';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->cachedPath = '/christmas-calendar';
        $this->pageAlias = 'christmas-calendar';
    }

    public function up()
    {

        if ($this->brand !== 'megariches') {
            return;
        }

        $pageId = $this->getPageID($this->cachedPath);
        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $pageId)
            ->where('name', '=', 'landing_bkg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $pageId,
                'name' => 'landing_bkg',
                'value' => 'mrchristmascalendar2024_bg.jpg'
            ]);
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID($this->cachedPath))
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
