<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddPdcSeasonalPromotionBoxAndPages extends Seeder
{
    protected string $tablePages;
    protected string $tableBoxes;

    private Connection $connection;
    private array $cachedPaths;
    private array $wbaAliases;
    private array $pageData;
    private array $boxData;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';

        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->wbaAliases = [];

        if ($this->isTargetBrand()) {
            $this->cachedPaths['pdc'] = '/pdc';
            $this->cachedPaths['pdc2'] = '/pdc2';
            $this->wbaAliases['pdc'] = 'pdc';
            $this->wbaAliases['pdc2'] = 'pdc2';
        }


        $this->pageData = [];
        $this->menuData = [];
        foreach ($this->wbaAliases as $alias => $aliasValue) {
            //paga data
            $this->pageData[] = [
                'parent_id' => $this->getDesktopPageParentID($alias),
                'alias' => $aliasValue,
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->cachedPaths[$alias],
            ];

            $this->pageData[] = [
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => $aliasValue,
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->cachedPaths[$alias],
            ];
        }
    }

    /**
     * Do the migration
     */
    public function up()
    {

        $this->init();

        // Run only for specified brands
        if (!$this->isTargetBrand()) {
            return;
        }


        // Add or Update Pages for both brands
        foreach ($this->pageData as $data) {
            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('alias', '=', $data['alias'])
                ->where('cached_path', '=', $data['cached_path'])
                ->exists();

            if (!$isPageExists) {
                $this->connection->table($this->tablePages)->insert($data);
            }
        }

        // Add or Update Boxes for both brands
        $this->boxData = [];
        foreach ($this->pageData as $data) {
            $this->boxData[] = [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID($data['cached_path'], $data['alias']),
            ];
        }

        foreach ($this->boxData as $data) {
            $isBoxExists = $this->connection
                ->table($this->tableBoxes)
                ->where('box_class', '=', $data['box_class'])
                ->where('page_id', '=', $data['page_id'])
                ->exists();

            if (!$isBoxExists) {
                $this->connection->table($this->tableBoxes)->insert(array_merge($data, ['priority' => 0]));
            }
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->init();

        if (!$this->isTargetBrand()) {
            return;
        }


        // Rebuild box data
        $this->boxData = [];
        foreach ($this->pageData as $data) {
            $this->boxData[] = [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID($data['cached_path'], $data['alias']),
            ];
        }

        // Delete Boxes for PageId dynamically
        foreach ($this->boxData as $data) {
            $this->connection
                ->table($this->tableBoxes)
                ->where('container', '=', $data['container'])
                ->where('box_class', '=', $data['box_class'])
                ->where('priority', '=', 0)
                ->where('page_id', '=', $data['page_id'])
                ->delete();
        }

        // Delete Page records dynamically
        foreach ($this->pageData as $data) {
            $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['alias'])
                ->where('filename', '=', $data['filename'])
                ->where('cached_path', '=', $data['cached_path'])
                ->delete();
        }
    }

    private function getDesktopPageParentID($alias): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', '.')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', $this->cachedPaths[$alias],)
            ->first();

        return (int)$page->page_id;
    }

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }

    private function getPageID(string $cache_path, string $alias): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', $alias)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }

    private function isTargetBrand(): bool
    {
        return $this->brand === phive('BrandedConfig')::BRAND_MRVEGAS;
    }
}
