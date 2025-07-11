<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\Extensions\Database\FManager as DB;


class UpdateWelcomeBonusReminderConfigForAllBrands extends Seeder
{
    private string $mailTable;
    private string $configTag;
    private array $mailsExecutionDate;
    private array $disableMailExecution;
    private string $brand;
    private array $configurations;
    private Connection $connection;

    public function init()
    {
        $this->mailTable = 'mails';
        $this->connection = DB::getMasterConnection();
        $this->mailsExecutionDate = ['config_value' => ''];
        $this->disableMailExecution = ['config_value' => 'off'];
        $this->configTag = 'mails';
        $this->brand = phive('BrandedConfig')->getBrand();
        

        $this->configurations = [
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'included_countries',
                "config_value" =>'',
                "config_type" => '{"type":"ISO2", "delimiter":" "}'
            ],
            [
                "config_name" => 'welcome.bonus_percentage_reminder',
                "config_tag" => 'included_countries',
                "config_value" =>'',
                "config_type" => '{"type":"ISO2", "delimiter":" "}'
            ],
        ];
    }

    public function up()
    {
        //Update mail execution dates and disable config for all brands except kungaslottet 
        if ($this->brand != phive('BrandedConfig')::BRAND_KUNGASLOTTET) {
            Config::where('config_name', 'welcome.bonus_reminder')
                    ->where('config_tag', $this->configTag)
                    ->update($this->mailsExecutionDate);

            Config::where('config_name', 'welcome.bonus_percentage_reminder')
                  ->where('config_tag', $this->configTag)
                  ->update($this->mailsExecutionDate);

            Config::where('config_name', 'welcome.bonus_percentage_reminder')
                    ->where('config_tag', 'bonus_percentage_reminder_email')
                    ->update($this->disableMailExecution);

            Config::where('config_name', 'welcome.bonus_reminder')
                    ->where('config_tag', 'bonus_reminder_email')
                    ->update($this->disableMailExecution);

            $this->connection
                    ->table($this->mailTable)
                    ->insert([
                        [
                            'mail_trigger' => 'welcome.bonus_percentage_reminder',
                            'subject' => 'mail.welcome.bonus_percentage_reminder.subject',
                            'content' => 'mail.welcome.bonus_percentage_reminder.content'
                        ],
                        [
                            'mail_trigger' => 'welcome.bonus_reminder',
                            'subject' => 'mail.welcome.bonus_reminder.subject',
                            'content' => 'mail.welcome.bonus_reminder.content'
                        ]]);
      }
        //Add new included_country config for all brands
        foreach ($this->configurations as $config){
                Config::create($config);
        }
    }

    public function down()
    {
        foreach ($this->configurations as $config) {
            Config::where('config_name', $config['config_name'])
                ->where('config_tag', $config['config_tag'])
                ->delete();
        }
    }


}