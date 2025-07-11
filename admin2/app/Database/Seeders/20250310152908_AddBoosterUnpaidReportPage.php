<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddBoosterUnpaidReportPage extends Seeder
{

    const PAGES_TABLE   = 'pages';
    const BOXES_TABLE   = 'boxes';
    const ATTR_TABLE    = 'boxes_attributes';
    const ALIAS         = 'booster-unpaid-report';
    const FILENAME      = 'diamondbet/generic.php';
    const PATH          = '/admin/booster-unpaid-report';

    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->createTranslations();

        $page_id = $this->createPage();
        if (!$page_id) {
            echo "Could not create page in BO" . PHP_EOL;
            return;
        }

        $this->createBox($page_id);
    }

    public function down()
    {
        $this->deleteTranslations();
    }

    private function createTranslations()
    {
        $exists = $this->connection
            ->table('localized_strings')
            ->where('alias', 'booster.release.error.no.funds')
            ->where('language', 'en')
            ->first();

        if (!empty($exists)) return;

        $this->connection
            ->table('localized_strings')
            ->insert([
                [
                    'alias' => 'booster.release.error.no.funds',
                    'language' => 'en',
                    'value' => 'No funds available to release',
                ]
            ]);
    }

    private function deleteTranslations()
    {
        $this->connection
            ->table('localized_strings')
            ->where('alias', 'booster.release.error.no.funds')
            ->where('language', 'en')
            ->delete();
    }

    private function createBox(int $page_id)
    {
        $box_id = $this->connection->table(self::BOXES_TABLE)->insertGetId([
            'container' => 'full',
            'box_class' => 'AdminBox',
            'priority'  => 0,
            'page_id'   => $page_id
        ]);

        if ($box_id) {
            $this->connection->table(self::ATTR_TABLE)->insert([
                'box_id'            => $box_id,
                'attribute_name'    => 'path',
                'attribute_value'   => '/phive/modules/Micro/html/booster_vault_unpaid.php',
            ]);

            $this->connection->table(self::ATTR_TABLE)->insert([
                'box_id'            => $box_id,
                'attribute_name'    => 'title',
                'attribute_value'   => 'Booster Vault Unpaid Report',
            ]);
        }
    }

    private function createPage(): int
    {
        $exists = $this->connection
            ->table(self::PAGES_TABLE)
            ->where('parent_id', '=', $this->getPage('/admin'))
            ->where('alias', '=', self::ALIAS)
            ->where('filename', '=', self::FILENAME)
            ->where('cached_path', '=', self::PATH)
            ->exists();

        if (!$exists) {
            return $this->connection
                ->table(self::PAGES_TABLE)
                ->insertGetId([
                    'parent_id' => $this->getPage('/admin'),
                    'alias'     => self::ALIAS,
                    'filename'  => self::FILENAME,
                    'cached_path' => self::PATH,
                ]);
        }

        return 0;
    }

    private function getPage(string $path, string $column = 'page_id'): int
    {
        return (int) $this->connection
            ->table(self::PAGES_TABLE)
            ->where('cached_path', '=', $path)
            ->first()->$column;
    }
}
