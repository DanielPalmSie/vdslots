<?php

use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\FManager as DB;

class AddMarketGroupsTranslationsForAllLanguages extends SeederTranslation
{
    private function getTranslationValues() {
        return [
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
            'sb.betting-group.14' => 'All',
            'sb.betting-group.15' => 'Period',
            'sb.betting-group.16' => 'Specials',
            'sb.betting-group.17' => 'Combo',
            'sb.betting-group.18' => 'Points',
            'sb.betting-group.19' => 'Quarters',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Games',
            'sb.betting-group.22' => 'Tries',
            'sb.betting-group.23' => 'Tries HT',
            'sb.betting-group.24' => 'Runs',
            'sb.betting-group.25' => 'Innings',
            'sb.betting-group.26' => '1st Inning',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => "180's"
        ];
    }
    protected array $data;
    
    private $connection;

    private string $localizedStringsConnectionsTable = 'localized_strings_connections';

    public function init()
    {
        parent::init();
        $this->connection = DB::getMasterConnection();

        $this->data = [
            'br' => $this->getTranslationValues(),
            'cl' => $this->getTranslationValues(),
            'de' => $this->getTranslationValues(),
            'es' => $this->getTranslationValues(),
            'fi' => $this->getTranslationValues(),
            'hi' => $this->getTranslationValues(),
            'it' => $this->getTranslationValues(),
            'ja' => $this->getTranslationValues(),
            'nl' => $this->getTranslationValues(),
            'no' => $this->getTranslationValues(),
            'pe' => $this->getTranslationValues(),
            'sv' => $this->getTranslationValues()
        ];
    }

    public function up()
    {
        parent::up();

        foreach ($this->data as $translation) {
            foreach ($translation as $alias => $value) {
                $exist = $this->connection
                    ->table($this->localizedStringsConnectionsTable)
                    ->where('target_alias', '=', $alias)
                    ->where('bonus_code', '=', 0)
                    ->first();

                if (!empty($exist)) {
                    continue;
                }

                $this->connection
                    ->table($this->localizedStringsConnectionsTable)
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

        foreach ($this->data as $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->localizedStringsConnectionsTable)
                    ->where('target_alias', $alias)
                    ->delete();
            }
        }
    }
}
