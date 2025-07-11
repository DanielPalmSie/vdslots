<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddSgaTermsAndConditionsPageDbet extends Migration
{
    private Connection $connection;
    private string $brand;
    private string $table_pages;
    private string $table_boxes;
    private string $table_boxes_attributes;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table_pages = 'pages';
        $this->table_boxes = 'boxes';
        $this->table_boxes_attributes = 'boxes_attributes';
    }

    public function up()
    {
        if ($this->brand === 'dbet') {

            $desktopPage = $this->connection->table($this->table_pages)
                ->where('parent_id', 106)
                ->where('alias', 'sga-svenska-regler-och-villkor')
                ->first();

            if ($desktopPage) {
                $desktop_page_id = $desktopPage->page_id;
            } else {
                $desktop_page_id = $this->connection->table($this->table_pages)
                    ->insertGetId([
                        'parent_id' => 106,
                        'alias' => 'sga-svenska-regler-och-villkor',
                        'filename' => 'diamondbet/generic.php',
                        'cached_path' => '/terms-and-conditions/sga-svenska-regler-och-villkor',
                    ]);
            }

            $mobilePage = $this->connection->table($this->table_pages)
                ->where('parent_id', 291)
                ->where('alias', 'sga-svenska-regler-och-villkor')
                ->first();

            if ($mobilePage) {
                $mobile_page_id = $mobilePage->page_id;
            } else {
                $mobile_page_id = $this->connection->table($this->table_pages)
                    ->insertGetId([
                        'parent_id' => 291,
                        'alias' => 'sga-svenska-regler-och-villkor',
                        'filename' => 'diamondbet/mobile.php',
                        'cached_path' => '/mobile/terms-and-conditions/sga-svenska-regler-och-villkor',
                    ]);
            }

            $this->connection->table($this->table_boxes)
                ->insert([
                    'container' => 'full',
                    'box_class' => 'SimpleExpandableBox',
                    'priority' => 0,
                    'page_id' => $desktop_page_id,
                ]);

            $this->connection->table($this->table_boxes)
                ->insert([
                    'container' => 'full',
                    'box_class' => 'SimpleExpandableBox',
                    'priority' => 0,
                    'page_id' => $mobile_page_id,
                ]);
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            $pages = $this->connection->table($this->table_pages)
                ->where('alias', 'sga-svenska-regler-och-villkor')
                ->get();

            foreach ($pages as $page) {
                $this->connection->table($this->table_boxes)
                    ->where('page_id', $page->page_id)
                    ->delete();
            }

            $this->connection->table($this->table_pages)
                ->where('alias', 'sga-svenska-regler-och-villkor')
                ->delete();
        }
    }
}
