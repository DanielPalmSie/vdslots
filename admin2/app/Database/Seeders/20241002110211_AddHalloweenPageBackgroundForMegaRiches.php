<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddHalloweenPageBackgroundForMegaRiches extends Seeder
{
    private string $tablePages;
    private string $tablePageSettings;
    private Connection $connection;
    private string $brand;
    protected string $cachedPath;
    protected string $alias;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->cachedPath = '/halloween';
        $this->alias = 'halloween';
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if ($this->brand !== 'megariches') {
            return;
        }

        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID($this->cachedPath))
            ->where('name', '=', 'landing_bkg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID($this->cachedPath),
                'name' => 'landing_bkg',
                'value' => 'mr_lpbackground_halloween.png'
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

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', $this->alias)
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }
}
