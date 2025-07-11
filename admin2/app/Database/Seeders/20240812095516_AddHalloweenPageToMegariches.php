<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddHalloweenPageToMegariches extends Seeder
{
    private Connection $connection;
    private string $pagesTable;
    private string $boxesTable;
    private string $localizedStringsTable;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->pagesTable = 'pages';
        $this->boxesTable = 'boxes';
        $this->localizedStringsTable = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $desktopPageExists = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/halloween')
            ->exists();

        $mobilePageExists = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', '/mobile/halloween')
            ->exists();

        if ($desktopPageExists || $mobilePageExists) {
            return;
        }

        $this->addPageAndBoxes();
        $this->addPageAndBoxes(true);
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $pageIds = $this->connection
            ->table($this->pagesTable)
            ->whereIn('cached_path', ['/mobile/halloween','/halloween'])
            ->pluck('page_id');

        $boxIds = $this->connection
            ->table($this->boxesTable)
            ->whereIn('page_id', $pageIds)
            ->pluck('box_id');

        foreach ($boxIds as $boxId) {
            $this->connection
                ->table($this->localizedStringsTable)
                ->where('alias', "simple.$boxId.html")
                ->delete();
        }

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
            'alias' => 'halloween',
            'filename' => $isMobile ? 'diamondbet/mobile.php' : 'diamondbet/generic.php',
            'cached_path' => $isMobile ? '/mobile/halloween' : '/halloween',
        ];

        $pageId = $this->connection
            ->table($this->pagesTable)
            ->insertGetId($page);

        $fullImageBox = [
            'container' => 'full',
            'box_class' => 'FullImageBox',
            'priority' => 0,
            'page_id' => $pageId,
        ];

        $this->connection
            ->table($this->boxesTable)
            ->insert($fullImageBox);

        $simpleExpandableBox = [
            'container' => 'full',
            'box_class' => 'SimpleExpandableBox',
            'priority' => 1,
            'page_id' => $pageId,
        ];

        $simpleExpandableBoxId = $this->connection
            ->table($this->boxesTable)
            ->insertGetId($simpleExpandableBox);

        $simpleExpandableBoxContent = [
            'alias' => "simple.$simpleExpandableBoxId.html",
            'language' => 'en',
            'value' => 'Mega Riches Halloween page (to be replaced with correct page contents)',
        ];

        $this->connection
            ->table($this->localizedStringsTable)
            ->insert($simpleExpandableBoxContent);
    }
}
