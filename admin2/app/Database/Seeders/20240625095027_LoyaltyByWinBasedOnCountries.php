<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class LoyaltyByWinBasedOnCountries extends Seeder
{

    /**
     * @var array|array[]
     */
    private array $config = [
        "config_name" => 'loyalty-by-wins',
        "config_tag" => 'loyalty-based-on-countries',
        "config_value" => '',
        "config_type" => '{"type":"ISO2","delimiter":" "}',
    ];

    public function init()
    {
    }

    /**
     * Up the seeds
     */
    public function up()
    {
        Config::create($this->config);
    }

    /**
     * Undo the seeder
     */
    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }
}
