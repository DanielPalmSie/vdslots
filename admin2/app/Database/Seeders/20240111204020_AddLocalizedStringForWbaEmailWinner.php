<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForWbaEmailWinner extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'wba.winner.prize.agree' => 'I understand that if I am a winner of one of the prize draws my name will be provided by Mr Vegas to West Bromwich Albion Football Club to allow them to announce the winner at the relevant WBA home match.'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert([
                            [
                                'alias' => $alias,
                                'language' => $language,
                                'value' => $value,
                            ]
                        ]);
                }

            }
        }
    }

    public function down()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}