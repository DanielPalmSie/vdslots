<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddMyAchievementsMissionOverviewTranslationsForMobile extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $connectionsTable;
    private string $tag;
    private array $aliases;

    public function init()
    {
        $this->aliases = [];
        $this->table = 'localized_strings';
        $this->connectionsTable = 'localized_strings_connections';
        $this->tag = 'mobile_app_localization_tag';
        $this->connection = DB::getMasterConnection();

        $this->connection
            ->table($this->table)
            ->where('alias', 'like', 'trophy.%.category')
            ->groupBy('alias')
            ->get()
            ->each(function($el) {
                return array_push($this->aliases, $el->alias);
            });
    }

    public function up()
    {
        foreach ($this->aliases as $alias)
        {
            $this->connection
                ->table($this->connectionsTable)
                ->updateOrInsert([
                    'target_alias' => $alias,
                    'bonus_code' => 0,
                    'tag' => $this->tag
                ]);
        }
    }

    public function down()
    {
        foreach ($this->aliases as $alias)
        {
            $this->connection
                 ->table($this->connectionsTable)
                 ->where('target_alias', $alias)
                 ->where('tag', $this->tag)
                 ->delete();
        }
    }
}
