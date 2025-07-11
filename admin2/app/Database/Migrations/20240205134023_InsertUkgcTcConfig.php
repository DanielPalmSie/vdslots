<?php

use App\Models\Config;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class InsertUkgcTcConfig extends Migration
{
    private array $config_items;

    protected $connection;


    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->config_items = [
            [
                'config_name' => 'tc-page',
                'config_tag' => 'license-gb',
                'config_value' => 'simple.1605.html',
                'config_type' => '{"type":"text"}',
            ],
            [
                'config_name' => 'tc-version',
                'config_tag' => 'license-gb',
                'config_value' => '2.10',
                'config_type' => '{"type":"number"}',
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->config_items as $config_item) {
            $exists = Config::shs()
                ->where('config_name', $config_item['config_name'])
                ->where('config_tag', $config_item['config_tag'])
                ->first();

            if (empty($exists)) {
                Config::shs()->insert($config_item);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->config_items as $config_item) {
            Config::shs()
                ->where('config_name', $config_item['config_name'])
                ->where('config_tag', $config_item['config_tag'])
                ->delete();
        }

    }
}
