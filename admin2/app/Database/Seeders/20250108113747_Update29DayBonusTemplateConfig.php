<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class Update29DayBonusTemplateConfig extends Seeder
{

    protected $table;

    protected $keys;

    public function init()
    {
        $this->table = 'config';

        // List of keys
        $this->keys = [
            'expire_time',
            'num_days',
            'cost',
            'reward',
            'bonus_name',
            'deposit_limit',
            'rake_percent',
            'bonus_code',
            'deposit_multiplier',
            'bonus_type',
            'exclusive',
            'bonus_tag',
            'type',
            'game_tags',
            'cash_percentage',
            'max_payout',
            'reload_code',
            'excluded_countries',
            'deposit_amount',
            'deposit_max_bet_percent',
            'bonus_max_bet_percent',
            'max_bet_amount',
            'included_countries',
            'fail_limit',
            'game_percents',
            'loyalty_percent',
            'allow_race',
            'top_up',
            'stagger_percent',
            'keep_winnings',
            'deposit_threshold',
            'award_id',
            'country_version',
            'brand_id'
        ];
    }

    public function up()
    {
        // Create a regex pattern to match the keys
        $pattern = '/\n*(' . implode('|', array_map('preg_quote', $this->keys)) . ')::/';

        DB::loopNodes(function ($connection) use ($pattern) {
             for ($x = 1; $x <= 15; $x++) {

                 $config_exits  = $connection->table($this->table)
                     ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                     ->where('config_tag', 'bonus-templates')
                     ->first();

                 if (!empty($config_exits)) {
                     $outputString = preg_replace($pattern, "\n$1::", $config_exits->config_value);
                     $updated_config  = ltrim($outputString, "\n");

                     $connection->table($this->table)
                         ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                         ->where('config_tag', 'bonus-templates')
                         ->update(['config_value' => $updated_config]);
                 }
             }
         }, true);

    }

    public function down()
    {
        // Create a regex pattern to match the keys
        $pattern = '/\n+(' . implode('|', array_map('preg_quote', $this->keys)) . ')::/';

        DB::loopNodes(function ($connection) use ($pattern) {
            for ($x = 1; $x <= 15; $x++) {

                $config_exits  = $connection->table($this->table)
                    ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                    ->where('config_tag', 'bonus-templates')
                    ->first();

                if (!empty($config_exits)) {
                    $outputString = preg_replace($pattern, "$1::", $config_exits->config_value);
                    $updated_config  = trim($outputString, "\n");

                    $connection->table($this->table)
                        ->where('config_name', '29daydeposit-newbonusoffers-mail-'.$x)
                        ->where('config_tag', 'bonus-templates')
                        ->update(['config_value' => $updated_config]);
                }
            }
        }, true);

    }
}
