<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddGamesRecommendationPageVideoslots extends Seeder
{
    private Connection $connection;
    private string $pagesTable;
    private string $boxesTable;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->pagesTable = 'pages';
        $this->boxesTable = 'boxes';
    }

    public function up()
    {
        if ($this->brand !== 'videoslots') {
            return;
        }

        $desktopPageExists = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/recommendations')
            ->exists();

        $mobilePageExists = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/mobile/recommendations')
            ->exists();

        if ($desktopPageExists || $mobilePageExists) {
            return;
        }

        $this->addPageAndBoxes();
        $this->addPageAndBoxes(true);
    }

    public function down()
    {
        if ($this->brand !== 'videoslots') {
            return;
        }

        $pageIds = $this->connection
            ->table($this->pagesTable)
            ->whereIn('cached_path', ['/mobile/recommendations', '/recommendations'])
            ->pluck('page_id');

        $boxIds = $this->connection
            ->table($this->boxesTable)
            ->whereIn('page_id', $pageIds)
            ->pluck('box_id');

        $this->connection
            ->table($this->boxesTable)
            ->whereIn('box_id', $boxIds)
            ->delete();

        $this->connection
            ->table($this->pagesTable)
            ->whereIn('page_id', $pageIds)
            ->delete();
    }

    private function addPageAndBoxes(bool $isMobile = false): void
    {
        $page = [
            'parent_id' => $isMobile ? 268 : 0,
            'alias' => 'recommendations',
            'filename' => $isMobile ? 'diamondbet/mobile.php' : 'diamondbet/generic.php',
            'cached_path' => $isMobile ? '/mobile/recommendations' : '/recommendations',
        ];

        $pageId = $this->connection
            ->table($this->pagesTable)
            ->insertGetId($page);

        $fullImageBox = [
            'container' => 'full',
            'box_class' => 'GamesRecommendationsBox',
            'priority' => 0,
            'page_id' => $pageId,
        ];

        $this->connection
            ->table($this->boxesTable)
            ->insert($fullImageBox);
    }
}
