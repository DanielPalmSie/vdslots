<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class Add29DayDepositEmailConfig extends Seeder
{
     /**
     * @var array|array[]
     */
    private array $config;

    public function init()
    {
        for($i = 1; $i <= 15; $i++ ) {
            $this->config[] = [
                "config_name" => "29daydeposit-newbonusoffers-mail-$i",
                "config_tag" => 'mails',
                "config_type" => '{"type":"number"}',
            ];
        }
        
    }

    public function up()
    {
        foreach($this->config as $config) {
            Config::create($config);
        }
        
    }

    public function down()
    {
        foreach($this->config as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}