<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class EnableTopJackpotsSliderForMegariches extends Seeder
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

        $this->setJpCounterOnBannerBoxes();

        $this->setTopJpSlider();
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->setJpCounterOnBannerBoxes(false);

        $this->setTopJpSlider(false);
    }

    private function setJpCounterOnBannerBoxes(bool $enable = true): void
    {
        $boxClasses = ['DynamicImageBox', 'JsBannerRotatorBox', 'MobileStartBox'];

        $boxIds = $this->connection
            ->table($this->boxesTable)
            ->whereIn('box_class', $boxClasses)
            ->pluck('box_id');

        foreach ($boxIds as $boxId) {
            $this->connection
                ->table($this->boxesAttributesTable)
                ->upsert(
                    ['box_id' => $boxId, 'attribute_name' => 'jp_counter', 'attribute_value' => $enable ? 1 : 0],
                    ['box_id' => $boxId, 'attribute_name' => 'jp_counter'],
                );
        }
    }

    private function setTopJpSlider(bool $enable = true): void
    {
        $pageIds = $this->connection
            ->table($this->pagesTable)
            ->whereIn('cached_path', ['/', '/.', '/mobile'])
            ->pluck('page_id');

        $boxClasses = ['MgGameChooseBox', 'MgMobileGameChooseBox'];

        $boxIds = $this->connection
            ->table($this->boxesTable)
            ->whereIn('page_id', $pageIds)
            ->whereIn('box_class', $boxClasses)
            ->pluck('box_id');

        foreach ($boxIds as $boxId) {
            $this->connection
                ->table($this->boxesAttributesTable)
                ->upsert(
                    ['box_id' => $boxId, 'attribute_name' => 'jp_counter', 'attribute_value' => $enable ? 1 : 0],
                    ['box_id' => $boxId, 'attribute_name' => 'jp_counter'],
                );

            $this->connection
                ->table($this->boxesAttributesTable)
                ->upsert(
                    ['box_id' => $boxId, 'attribute_name' => 'top_jp_games_slider', 'attribute_value' => $enable ? 1 : 0],
                    ['box_id' => $boxId, 'attribute_name' => 'top_jp_games_slider'],
                );

            $this->connection
                ->table($this->boxesAttributesTable)
                ->upsert(
                    ['box_id' => $boxId, 'attribute_name' => 'top_jp_games_slider_items_count', 'attribute_value' => 12],
                    ['box_id' => $boxId, 'attribute_name' => 'top_jp_games_slider_items_count'],
                );
        }
    }
}
