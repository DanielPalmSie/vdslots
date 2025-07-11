<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;


class AddLocalizedStringsForOrdinalNumberForLeaderboard extends SeederTranslation
{
    private Connection $connection;
    private string $table = 'localized_strings';

    protected array $data = [
        'de' => [
            'mp.first.prize' => 'er',
            'mp.second.prize' => 'er',
            'mp.third.prize' => 'er',
            'mp.other.prize' => 'er'
        ],
        'es' => [
            'mp.first.prize' => 'º',
            'mp.second.prize' => 'º',
            'mp.third.prize' => 'º',
            'mp.other.prize' => 'º'
        ],
        'fi' => [
            'mp.first.prize' => '. sija',
            'mp.second.prize' => '. sija',
            'mp.third.prize' => '. sija',
            'mp.other.prize' => '. sija'
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