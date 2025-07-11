<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateWelcomePercentageReminderSmsContent extends Seeder
{
    private string $table;
    private array $data;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->data = [
            [
                'language' => 'en',
                'alias' => 'sms.welcome.bonus_pecentage_reminder.content',
                'value' => 'Hey __USERNAME__ , reminder: your progress towards the next 20k Welcome Bonus payout is __VALUE__ , which means __AMOUNT__ % is complete. Keep playing to reach the rest!',
            ]
        ];
    }

    public function up()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->where('alias', '=', 'sms.welcome.bonus_pecentage_reminder.content')
            ->delete();
    }
}
