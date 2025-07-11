<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddRugbyLocalizationStrings extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sb.betting-group.22' => 'Tries',
            'sb.betting-group.23' => 'Tries HT'
        ]
    ];

    private $connection;

    private string $localizedStringsConnectionsTable = 'localized_strings_connections';

    public function init()
    {
        parent::init();
        $this->connection = DB::getMasterConnection();
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