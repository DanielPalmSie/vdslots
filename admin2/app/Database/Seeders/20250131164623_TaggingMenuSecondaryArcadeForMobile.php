<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class TaggingMenuSecondaryArcadeForMobile extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $alias;
    private string $tag;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings_connections';
        $this->alias = 'menu.secondary.arcade';
        $this->tag = 'mobile_app_localization_tag';
    }

    public function up()
    {
        $this->init();

        $isAliasExists = $this->connection
            ->table($this->table)
            ->where('target_alias', '=', $this->alias)
            ->where('tag', '=', $this->tag)
            ->exists();

        if (!$isAliasExists) {
            $this->connection
                ->table($this->table)
                ->insert([
                    'target_alias' => $this->alias,
                    'tag' => $this->tag,
                ]);
        }
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
