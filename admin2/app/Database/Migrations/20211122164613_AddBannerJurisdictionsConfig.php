<?php

use App\Models\Config;
use Phpmig\Migration\Migration;

class AddBannerJurisdictionsConfig extends Migration
{
    private string $table;
    private array $config;

    public function init()
    {
        $this->table = 'config';
        $this->config = [
            'config_name' => 'banner_jurisdictions',
            'config_tag' => 'jurisdictions',
            'config_type' => '{"type":"text","delimiter":" "}',
            'config_value' => 'dgoj'
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $exists = Config::shs()
            ->where('config_name', $this->config['config_name'])
            ->where('config_tag', $this->config['config_tag'])
            ->first();

        if (empty($exists)) {
            Config::shs()->insert($this->config);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Config::shs()
            ->where('config_name', $this->config['config_name'])
            ->where('config_tag', $this->config['config_tag'])
            ->delete();
    }
}
