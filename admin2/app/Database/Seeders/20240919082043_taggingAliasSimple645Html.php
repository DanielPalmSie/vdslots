<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class taggingAliasSimple645Html extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $alias;
    private string $tag;

    public function init()
    {
        $this->table = 'localized_strings_connections';
        $this->alias = 'simple.645.html';
        $this->tag = 'mobile_app_localization_tag';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->insert([
                'target_alias' => $this->alias,
                'tag' => $this->tag,
            ]);
    }

    public function down()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->where('target_alias', '=', $this->alias)
            ->where('tag', '=', $this->tag)
            ->delete();
    }
}
