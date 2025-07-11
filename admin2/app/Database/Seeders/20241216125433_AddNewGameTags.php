<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;

class AddNewGameTags extends Seeder
{
    private string $table = 'game_tags';
    private Connection $connection;

    protected array $data = [
        [
            'alias'              => 'gameofweek.cgames',
            'excluded_countries' => '',
            'filterable'         => 1
        ],
        [
            'alias'              => 'livecasinospotlight.cgames',
            'excluded_countries' => '',
            'filterable'         => 1
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->data as $game_tag) {
            $game_tag_exists = $this->connection
                ->table($this->table)
                ->where('alias', $game_tag['alias'])
                ->exists();

            if (!$game_tag_exists) {
                $this->connection
                    ->table($this->table)
                    ->insert($game_tag);
            }
        }
    }

    public function down()
    {
        foreach ($this->data as $game_tag) {
            $this->connection
                ->table($this->table)
                ->where('alias', $game_tag['alias'])
                ->delete();
        }
    }
}
