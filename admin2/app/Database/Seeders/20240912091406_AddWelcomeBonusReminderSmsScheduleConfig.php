<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddWelcomeBonusReminderSmsScheduleConfig extends Seeder
{
    
    private array $config;
    
    public function init()
    {
        $this->config = [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'bonus_reminder_sms',
                "config_value" =>'on',
                "config_type" => '{"type":"choice","values":["on","off"]}"}'
        ];
    }

    public function up()
    {
            Config::create($this->config);
    }

    public function down()
    {
            Config::where('config_tag', $this->config['config_tag'])->delete();   
    }
}
