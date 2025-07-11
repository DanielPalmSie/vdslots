<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;


class InsertDGOJTcIntoLocalizedStrings extends Migration
{
    private string $table;
    private string $config_table;
    private array $items;

    private $tc_config;
    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->config_table = 'config';

        $this->connection = DB::getMasterConnection();

        $this->items = [
            'es' => [
                'alias' => '',
                'language' => 'es',
                'value' => '',
            ],
            'en' => [
                'alias' => '',
                'language' => 'en',
                'value' => '',
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // Get proper alias value
        $this->tc_config = $this->connection
            ->table($this->config_table)
            ->where('config_name', 'tc-page')
            ->where('config_tag', 'license-es')
            ->first();
        // Insert dgoj tc strings
        foreach ($this->items as $item_key => $item) {
            $item['alias'] = $this->tc_config->config_value;

            $item_exists = $this->connection
                ->table($this->table)
                ->where('language', $item['language'])
                ->where('alias', $item['alias'])
                ->first();

            if (!empty($item_exists)) {
                continue;
            } else {
                $this->connection
                    ->table($this->table)
                    ->insert($item);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // Get proper alias value
        $this->tc_config = $this->connection
            ->table($this->config_table)
            ->where('config_name', 'tc-page')
            ->where('config_tag', 'license-es')
            ->first();

        // Delete dgoj tc strings
        foreach ($this->items as $item) {
            $item['alias'] = $this->tc_config->config_value;

            $this->connection
                ->table($this->table)
                ->where('language', $item['language'])
                ->where('alias', $item['alias'])
                ->delete();
        }

    }
}
