<?php

namespace App\Extensions\Database\Seeder;

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class SeederTranslation extends Seeder
{
    protected array $data = [];
    protected array $stringConnectionsData = [];
    private string $table = 'localized_strings';
    private string $localizedStringsConnectionsTable = 'localized_strings_connections';
    private $connection;

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
                    continue;
                }

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

        if (!empty($this->stringConnectionsData)) {
            $connectionAliasData = array_first($this->data);
            $stringConnectionsInsertData = [];

            foreach ($connectionAliasData as $key => $value) {
                $stringConnection = $this->connection
                    ->table($this->localizedStringsConnectionsTable)
                    ->where('target_alias', $key)
                    ->where('tag', $this->stringConnectionsData['tag'])
                    ->where('bonus_code', $this->stringConnectionsData['bonus_code'])
                    ->first();

                if (!empty($stringConnection)) {
                    continue;
                }

                $stringConnectionsInsertData[] =
                    [
                        'target_alias' => $key,
                        'tag' => $this->stringConnectionsData['tag'],
                        'bonus_code' => $this->stringConnectionsData['bonus_code']
                    ];
            }

            if(!empty($stringConnectionsInsertData)) {
                $this->connection
                    ->table($this->localizedStringsConnectionsTable)
                    ->insert($stringConnectionsInsertData);
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

        if (!empty($this->stringConnectionsData)) {
            $connectionAliasData = array_keys(array_first($this->data));
            $this->connection
                ->table($this->localizedStringsConnectionsTable)
                ->whereIn('target_alias', $connectionAliasData)
                ->where('tag', $this->stringConnectionsData['tag'])
                ->where('bonus_code', $this->stringConnectionsData['bonus_code'])
                ->delete();
        }

    }
}
