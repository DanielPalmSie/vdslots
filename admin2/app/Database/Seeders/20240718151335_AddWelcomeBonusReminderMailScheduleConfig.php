<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddWelcomeBonusReminderMailScheduleConfig extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $configurations;
    
    public function init()
    {
        $this->configurations = [
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'mails',
                "config_value" =>'1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31',
                "config_type" => '{"type":"template", "next_data_delimiter":",", "format":"<:Number><delimiter>"}'
            ],
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'bonus_reminder_email',
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
