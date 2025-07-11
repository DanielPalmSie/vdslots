<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddEnhancedOfferDurationConfig extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'enhanced-offer-duration',
            "config_tag" => 'mails',
            "config_value" => 30,
            "config_type" => '{"type":"number"}',
        ];
    
    }

    public function up()
    {   
        Config::create($this->config);
    }

    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }
}