<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RemoveLiveCasinoInMgMobileGameChooseBox extends Migration
{
    /** @var string */
    private string $attribute_table;

    /** @var Connection */
    private Connection $connection;

    /** @var string */
    private string $tag;

    /** @var string  */
    private string $seperator;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->tag = 'live-casino';
        $this->attribute_table = 'boxes_attributes';
        $this->seperator = ',';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $box = $this->getBox();

        if (!in_array($this->tag, $box->tags)) {
            return;
        }

        $this->connection->table($this->attribute_table)
            ->where('box_id', '=', $box->box_id)
            ->where('attribute_name', '=', 'tags')
            ->update([
                'attribute_value' => $this->buildTagsValue($box->tags, 'remove')
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $box = $this->getBox();

        if (in_array($this->tag, $box->tags)) {
            return;
        }

        $this->connection->table($this->attribute_table)
            ->where('box_id', '=', $box->box_id)
            ->where('attribute_name', '=', 'tags')
            ->update([
                'attribute_value' => $this->buildTagsValue($box->tags, 'add')
            ]);
    }

    /**
     * Will return box MgMobileGameChooseBox with attributes for page mobile.
     *
     * @return mixed
     */
    private function getBox()
    {
        $box = $this->connection->table('boxes')
            ->join($this->attribute_table, 'boxes.box_id', '=', 'boxes_attributes.box_id')
            ->join('pages', 'boxes.page_id', '=', 'pages.page_id')
            ->where('box_class', '=', 'MgMobileGameChooseBox')
            ->where('pages.cached_path', '=', '/mobile')
            ->where('boxes_attributes.attribute_name', '=', 'tags')
            ->select(['boxes_attributes.attribute_value', 'boxes_attributes.box_id'])
            ->first();

        $box->tags = array_filter(explode($this->seperator, $box->attribute_value));

        return $box;
    }

    /**
     * Will build value for tags attribute_value
     */
    private function buildTagsValue(array $tags, string $action): string
    {
        if($action === 'add') {
            array_splice($tags, 1, 0, $this->tag);
        }

        if($action === 'remove') {
            unset($tags[array_search($this->tag, $tags)]);
        }

        return join($this->seperator, $tags);
    }
}
