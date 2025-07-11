<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AdjustTopJackpotsSliderAttributes extends Seeder
{
    private Connection $connection;
    private string $pagesTable;
    private string $boxesTable;
    private string $boxesAttributesTable;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->pagesTable = 'pages';
        $this->boxesTable = 'boxes';
        $this->boxesAttributesTable = 'boxes_attributes';
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->setLinkOnPage('/.', true);
        $this->setLinkOnPage('/jackpots', false);
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->setLinkOnPage('/.', true);
        $this->setLinkOnPage('/jackpots', true);
    }

    private function setLinkOnPage(string $page, bool $enable): void
    {
        $page = $this->connection
            ->table($this->pagesTable)
            ->where('cached_path', $page)
            ->first();

        $pageId = $page->page_id;

        $box = $this->connection
            ->table($this->boxesTable)
            ->where('page_id', $pageId)
            ->where('box_class', 'MgGameChooseBox')
            ->first();

        $boxId = $box->box_id;

        $this->connection
            ->table($this->boxesAttributesTable)
            ->upsert(
                ['box_id' => $boxId, 'attribute_name' => 'top_jp_slider_link', 'attribute_value' => $enable ? 1 : 0],
                ['box_id' => $boxId, 'attribute_name' => 'top_jp_slider_link'],
            );
    }
}
