<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;


class InsertDGOJTcConfig extends Migration
{
    private string $config_table;
    private string $pages_table;
    private string $boxes_table;

    private array $config_items;
    private array $pages_items;
    private array $boxes_items;

    private array $pages_ids;
    private array $boxes_ids;

    protected $connection;


    public function init()
    {
        $this->config_table = 'config';
        $this->pages_table = 'pages';
        $this->boxes_table = 'boxes';

        $this->connection = DB::getMasterConnection();
        $this->config_items = [
            'desktop' => [
                'config_name' => 'tc-page',
                'config_tag' => 'license-es',
                'config_value' => '',
                'config_type' => '{"type":"text"}',
            ],
            'version' => [
                'config_name' => 'tc-version',
                'config_tag' => 'license-es',
                'config_value' => '1.0',
                'config_type' => '{"type":"number"}',
            ]
        ];
        $this->pages_items = [
            'desktop' => [
                'parent_id' => '106',
                'alias' => 'dgoj',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/terms-and-conditions/dgoj',
            ],
            'mobile' => [
                'parent_id' => '291',
                'alias' => 'dgoj',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/terms-and-conditions/dgoj',
            ]
        ];
        $this->boxes_items = [
            'desktop' => [
                'container' => 'full',
                'box_class' => 'SimpleExpandableBox',
                'priority' => 1,
                'page_id' => '',
            ],
            'mobile' => [
                'container' => 'full',
                'box_class' => 'SimpleExpandableBox',
                'priority' => 0,
                'page_id' => '',
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // Add pages and store ids in an array
        foreach ($this->pages_items as $page_device => $page_item) {
            $page_exists = $this->connection
                ->table($this->pages_table)
                ->where('cached_path', $page_item['cached_path'])
                ->where('alias', $page_item['alias'])
                ->first();

            if (!empty($page_exists)) {
                $this->pages_ids[$page_device] = $page_exists->page_id;
            } else {
                $created_page = $this->connection
                    ->table($this->pages_table)
                    ->insert($page_item);

                if ($created_page) {
                    $page_exists = $this->connection
                        ->table($this->pages_table)
                        ->where('cached_path', $page_item['cached_path'])
                        ->where('alias', $page_item['alias'])
                        ->first();

                    $this->pages_ids[$page_device] = $page_exists->page_id;
                }
            }
        }
        // Add boxes and store ids in an array
        foreach ($this->boxes_items as $box_device => $box_item) {
            $box_item['page_id'] = $this->pages_ids[$box_device];
            $box_exists = $this->connection
                ->table($this->boxes_table)
                ->where('page_id', $box_item['page_id'])
                ->first();

            if (!empty($box_exists)) {
                $this->boxes_ids[$box_device] = $box_exists->box_id;
            } else {
                $created_box = $this->connection
                    ->table($this->boxes_table)
                    ->insert($box_item);

                if ($created_box) {
                    $box_exists = $this->connection
                        ->table($this->boxes_table)
                        ->where('page_id', $box_item['page_id'])
                        ->first();

                    $this->boxes_ids[$box_device] = $box_exists->box_id;

                }
            }
        }
        // Add configs
        foreach ($this->config_items as $config_key => $config_item) {
            if (strcmp($config_key, 'desktop') == 0) {
                $config_item['config_value'] = 'simple.' . $this->boxes_ids[$config_key] . '.html';
            }

            $config_exists = $this->connection
                ->table($this->config_table)
                ->where('config_name', $config_item['config_name'])
                ->where('config_tag', $config_item['config_tag'])
                ->first();

            if (!empty($config_exists)) {
                $this->connection
                    ->table($this->config_table)
                    ->where('id', $config_exists->id)
                    ->update(['config_value' => $config_item['config_value']]);
            }else{
                $this->connection
                    ->table($this->config_table)
                    ->insert($config_item);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // Get pages, store ids in an array and delete
        foreach ($this->pages_items as $page_device => $page_item) {
            $page_exists = $this->connection
                ->table($this->pages_table)
                ->where('cached_path', $page_item['cached_path'])
                ->where('alias', $page_item['alias'])
                ->first();

            if (!empty($page_exists)) {
                $this->pages_ids[$page_device] = $page_exists->page_id;
                $this->connection
                    ->table($this->pages_table)
                    ->where('page_id', $page_exists->page_id)
                    ->delete();
            }
        }

        // Delete boxes
        foreach ($this->pages_ids as $page_id) {
            $this->connection
                ->table($this->boxes_table)
                ->where('page_id', $page_id)
                ->delete();
        }

        // Delete config
        foreach ($this->config_items as $config_item) {
            $this->connection
                ->table($this->config_table)
                ->where('config_name', $config_item['config_name'])
                ->where('config_tag', $config_item['config_tag'])
                ->delete();
        }

    }
}
