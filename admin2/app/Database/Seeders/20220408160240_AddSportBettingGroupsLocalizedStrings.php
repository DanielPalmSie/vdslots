<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddSportBettingGroupsLocalizedStrings extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sb.betting-group.1' => 'Main',
            'sb.betting-group.2' => 'Goals',
            'sb.betting-group.3' => '1st Half',
            'sb.betting-group.4' => '2nd Half',
            'sb.betting-group.5' => 'Corners',
            'sb.betting-group.6' => 'Bookings',
            'sb.betting-group.7' => 'Scorers',
            'sb.betting-group.8' => 'Player',
            'sb.betting-group.9' => 'Specials',
            'sb.betting-group.10' => 'Rapid Markets',
            'sb.betting-group.11' => '5 Minute Markets',
            'sb.betting-group.12' => '10 Minute Markets',
            'sb.betting-group.13' => '15 Minute Markets',
            'sb.betting-group.14' => 'All'
        ]
    ];

    private $connection;

    private string $localized_strings_connections_table = 'localized_strings_connections';

    public function init()
    {
        parent::init();
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        parent::up();

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exist = $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->where('target_alias', '=', $alias)
                    ->where('bonus_code', '=', 0)
                    ->first();

                if (!empty($exist)) {
                    continue;
                }

                $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->insert([
                        [
                            'target_alias' => $alias,
                            'bonus_code' => 0,
                            'tag' => 'sb',
                        ]
                    ]);
            }
        }
    }

    public function down()
    {
        parent::down();

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->where('target_alias', $alias)
                    ->delete();
            }
        }
    }
}