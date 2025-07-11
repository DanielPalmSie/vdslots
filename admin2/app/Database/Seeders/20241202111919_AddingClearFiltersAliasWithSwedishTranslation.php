<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddingClearFiltersAliasWithSwedishTranslation extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $connections_table;
    private string $alias;
    private string $tag;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connections_table = 'localized_strings_connections';
        $this->alias = 'mobile.app.clear.filters';
        $this->tag = 'mobile_app_localization_tag';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->insert([
                'alias' => $this->alias,
                'language' => 'en',
                'value' => 'Clear Filters'
            ]);

        $this->connection
            ->table($this->table)
            ->insert([
                'alias' => $this->alias,
                'language' => 'sv',
                'value' => 'Rensa filtrar'
            ]);

        $this->connection
            ->table($this->connections_table)
            ->insert([
                'target_alias' => $this->alias,
                'bonus_code' => '0',
                'tag' => $this->tag,
            ]);
    }

    public function down()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', $this->alias)
            ->where('language', '=', 'en')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', $this->alias)
            ->where('language', '=', 'sv')
            ->delete();

        $this->connection
            ->table($this->connections_table)
            ->where('target_alias', '=', $this->alias)
            ->where('tag', '=', $this->tag)
            ->delete();
    }
}
