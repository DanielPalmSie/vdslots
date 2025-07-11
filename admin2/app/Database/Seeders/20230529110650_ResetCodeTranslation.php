<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class ResetCodeTranslation extends Seeder
{
    private Connection $connection;
    private string $table;
    protected string $alias = 'reset.code';
    protected string $translation = 'Reset code';

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->upsert([
                'language' => 'en',
                'alias' => $this->alias,
                'value' => $this->translation
            ], ['language', 'alias']);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', $this->alias)
            ->where('language', 'en')
            ->delete();
    }
}
