<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddWelcomeBonusReminderSmsLocalizedString extends Seeder
{
    private string $table;
    private array $data;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->data = [
            [
                'language' => 'en',
                'alias' => 'sms.welcome.bonus_reminder.content',
                'value' => 'Your Kungaslottet bonus is waiting! Don\'t let it expire – claim now: https://www.kungaslottet.se/welcome-bonus/ to start playing today!',
            ],
            [
                'language' => 'sv',
                'alias' => 'sms.welcome.bonus_reminder.content',
                'value' => 'Din Kungaslottet-bonus väntar! Låt den inte gå ut – hämta nu: https://www.kungaslottet.se/welcome-bonus/ och börja spela idag!',
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
            ->where('alias', '=', 'sms.welcome.bonus_reminder.content')
            ->delete();
    }
}
