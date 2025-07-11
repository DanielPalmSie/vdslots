<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateWelcomeBonusReminderMailScheduleConfig extends Seeder
{
    public function up()
    {
        Config::where('config_name', 'welcome.bonus_reminder')
            ->where('config_tag', 'mails')
            ->update([
                'config_value' => 'friday',
                'config_type'  => '{"type":"template","next_data_delimiter":",","format":"<:String><delimiter>"}'
            ]);
    }

    public function down()
    {
        Config::where('config_name', 'welcome.bonus_reminder')
            ->where('config_tag', 'mails')
            ->update([
                'config_value' => '',
                'config_type'  => '{"type":"template", "next_data_delimiter":",", "format":"<:Number><delimiter>"}'
            ]);
    }
}
