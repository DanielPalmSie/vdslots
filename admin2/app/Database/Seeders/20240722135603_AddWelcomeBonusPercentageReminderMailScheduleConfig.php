<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddWelcomeBonusPercentageReminderMailScheduleConfig extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $configurations;
    
    public function init()
    {
        $this->configurations = [
            [
                "config_name" => 'welcome.bonus_percentage_reminder',
                "config_tag" => 'mails',
                "config_value" =>'friday',
                "config_type" => '{"type":"template", "next_data_delimiter":",", "format":"<:Number><delimiter>"}'
            ],
            [
                "config_name" => 'welcome.bonus_percentage_reminder',
                "config_tag" => 'bonus_percentage_reminder_email',
                "config_value" =>'on',
                "config_type" => '{"type":"choice","values":["on","off"]}"}'
                
            ],
            [
                "config_name" => 'welcome.bonus_percentage_reminder_sms',
                "config_tag" => 'bonus_percentage_reminder_sms',
                "config_value" =>'on',
                "config_type" => '{"type":"choice","values":["on","off"]}"}'
                
            ],
        ];
    }

    public function up()
    {
        foreach ($this->configurations as $config){
            Config::create($config);
        }
        
    }

    public function down()
    {
        foreach ($this->configurations as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}
