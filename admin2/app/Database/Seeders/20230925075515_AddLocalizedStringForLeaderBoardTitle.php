<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;


class AddLocalizedStringForLeaderBoardTitle extends SeederTranslation
{

    private $connection;
    private string $localizedStringsConnectionsTable = 'localized_strings_connections';

    protected array $data = [
        'en' => [
            'mp.battle.leaderboard.title' => 'Battle Leader Board'
        ]
    ];

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
                            'tag' => 'mp',
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