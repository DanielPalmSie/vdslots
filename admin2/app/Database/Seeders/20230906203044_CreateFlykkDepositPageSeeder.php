<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class CreateFlykkDepositPageSeeder extends Seeder
{
    private string $tablePages = 'pages';
    private Connection $connection;

    private const ALIAS = 'flykk';
    private const FILENAME = 'diamondbet/html/payments/flykk/deposit.php';

    private array $platforms = [
        'desktop' => [
            'parentFilename' => 'diamondbet/clean.php',
            'parentCachedPath' => '/cashier/deposit',
            'cachedPath' => '/cashier/deposit/flykk'
        ],
        'mobile' => [
            'parentFilename' => 'diamondbet/mobile.php',
            'parentCachedPath' => '/mobile/cashier/deposit',
            'cachedPath' => '/mobile/cashier/deposit/flykk'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->platforms as $platform) {
            $this->migratePlatform($platform);
        }
    }

    public function down()
    {
        foreach ($this->platforms as $platform) {
            $this->rollbackPlatform($platform);
        }
    }

    private function migratePlatform(array $platform)
    {
        if (!$this->isFlykkPageExists($platform)) {
            $this->createFlykkPage($platform);
        }
    }

    private function rollbackPlatform(array $platform)
    {
        $page = $this->getFlykkPageQuery($platform);
        $this->doPageDelete($page);
    }

    private function isFlykkPageExists(array $platform): bool
    {
        return $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID($platform))
            ->where('alias', '=', self::ALIAS)
            ->where('filename', '=', self::FILENAME)
            ->where('cached_path', '=', $platform['cachedPath'])
            ->exists();
    }

    private function createFlykkPage(array $platform): void
    {
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => $this->getPageParentID($platform),
            'alias' => self::ALIAS,
            'filename' => self::FILENAME,
            'cached_path' => $platform['cachedPath']
        ]);
    }

    private function getPageParentID(array $platform): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'deposit')
            ->where('filename', '=', $platform['parentFilename'])
            ->where('cached_path', '=', $platform['parentCachedPath'])
            ->first();

        return (int)$page->page_id;
    }

    private function getFlykkPageQuery(array $platform)
    {
        return $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID($platform))
            ->where('alias', '=', self::ALIAS)
            ->where('filename', '=', self::FILENAME)
            ->where('cached_path', '=', $platform['cachedPath']);
    }

    private function doPageDelete($page)
    {
        $pageInfo = $page->first();
        if (!empty($pageInfo->page_id) && $this->isPageUnused($pageInfo->page_id)) {
            $page->delete();
        }
    }

    private function isPageUnused(int $pageId): bool
    {
        return $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $pageId)
                ->count() === 0;
    }
}
