<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RemoveFaqEncorePageMegariches extends Migration
{
    protected Connection $connection;
    protected string $brand;
    protected string $translation_alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->translation_alias = 'simple.1626.html';
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            $pages = $this->connection
                ->table('pages')
                ->where('alias', 'faq-encore')
                ->get();

            foreach ($pages as $page) {
                $this->connection
                    ->table('boxes')
                    ->where('page_id', $page->page_id)
                    ->delete();

                $this->connection
                    ->table('pages')
                    ->where('page_id', $page->page_id)
                    ->delete();
            }

            $this->connection
                ->table('localized_strings')
                ->where('alias', $this->translation_alias)
                ->delete();
        }
    }
}
