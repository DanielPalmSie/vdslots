<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddMarketGroupsLocalizedStrings extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sb.betting-group.15' => 'Period',
            'sb.betting-group.16' => 'Specials',
            'sb.betting-group.17' => 'Combo',
        ]
    ];

    private $connection;

    private string $table_localized_strings_connections = 'localized_strings_connections';

    public function init()
    {
        parent::init();
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        parent::up();

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {

                $exist = $this->connection
                    ->table($this->table_localized_strings_connections)
                    ->where('target_alias', '=', $alias)
                    ->where('bonus_code', '=', 0)
                    ->first();

                if (!empty($exist)) {
                    continue;
                }

                $this->connection
                    ->table($this->table_localized_strings_connections)
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

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table_localized_strings_connections)
                    ->where('target_alias', $alias)
                    ->delete();
            }
        }
    }
}
