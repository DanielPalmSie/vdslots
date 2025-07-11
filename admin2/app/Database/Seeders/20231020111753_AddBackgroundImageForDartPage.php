<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddBackgroundImageForDartPage extends Seeder
{
    private string $tablePages;
    private string $tablePageSettings;
    private Connection $connection;

    protected string $cachedPath;
    protected string $dartsAlias;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();

        $this->cachedPath = '/darts';
        $this->dartsAlias = 'darts';
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Page setting record process
        |--------------------------------------------------------------------------
        */
        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID($this->cachedPath))
            ->where('name', '=', 'landing_bkg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID($this->cachedPath),
                'name' => 'landing_bkg',
                'value' => 'MV-BG.jpg'
            ]);
        }

    }


    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
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
            ->where('alias', '=', $this->dartsAlias)
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }


}