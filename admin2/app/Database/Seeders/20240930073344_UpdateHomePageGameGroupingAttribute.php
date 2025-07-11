<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateHomePageGameGroupingAttribute extends Seeder
{

    private string $tableBoxes;
    private string $tablePages;
    private string $tableBoxAttributes;
    private Connection $connection;
    private string $brand;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->tableBoxAttributes = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

    }

    public function up()
    {

        if ($this->brand !== 'dbet') {
            return;
        }
        $mobilePageId = $this->getMobilePageParentID();
        $exists =  $this->connection
            ->table($this->tableBoxes)
            ->where('box_class', 'MgMobileGameChooseBox')
            ->where('page_id', $mobilePageId)
            ->exists();

        if($exists) {
            $boxes = $this->connection
                ->table($this->tableBoxes)
                ->where('box_class', 'MgMobileGameChooseBox')
                ->where('page_id', $mobilePageId)
                ->first();

            $boxId = (int) $boxes->box_id;

            $this->connection
                ->table($this->tableBoxAttributes)
                ->where('box_id', $boxId)
                ->where('attribute_name', 'show_grouped')
                ->update(['attribute_value' => 'yes']);
        }


    }


    public function down()
    {

        if ($this->brand !== 'dbet') {
            return;
        }

        $mobilePageId = $this->getMobilePageParentID();
        $exists =  $this->connection
            ->table($this->tableBoxes)
            ->where('box_class', 'MgMobileGameChooseBox')
            ->where('page_id', $mobilePageId)
            ->exists();

        if($exists) {
            $boxes = $this->connection
                ->table($this->tableBoxes)
                ->where('box_class', 'MgMobileGameChooseBox')
                ->where('page_id', $mobilePageId)
                ->first();

            $boxId = (int) $boxes->box_id;

            $this->connection
                ->table($this->tableBoxAttributes)
                ->where('box_id', $boxId)
                ->where('attribute_name', 'show_grouped')
                ->update(['attribute_value' => 'no']);
        }


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

}
