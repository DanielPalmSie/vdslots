<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class AddCheckToShowPromotionPage extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $config;

    public function init()
    {
        $this->config = [
            [
                "config_name" => 'show-promotion-page',
                "config_tag" => 'enable-promo-page',
                "config_value" => 'no',
                "config_type" => '{"type":"choice", "values":["yes","no"]}',
            ],
        ];
    }

    /**
     * Up the seeds
     */
    public function up()
    {
        foreach ($this->config as $config) {
            Config::create($config);
        }
    }

    /**
     * Undo the seeder
     */
    public function down()
    {
        foreach ($this->config as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }

}
