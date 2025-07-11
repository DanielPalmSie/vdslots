<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddSameDayAndNextDayTranslations extends Migration
{
    /** @var string */
    protected $table;
    protected $tableConnections;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->tableConnections = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    private $languageStrings = [
        [
            'alias' => 'sb.datetime.today',
            'language' => 'en',
            'value' => 'Today'
        ],
        [
            'alias' => 'sb.datetime.tomorrow',
            'language' => 'en',
            'value' => 'Tomorrow'
        ]
    ];

    private $languageConnections = [
        [
            'target_alias' => 'sb.datetime.today',
            'bonus_code' => 0,
            'tag' => 'sb'
        ],
        [
            'target_alias' => 'sb.datetime.tomorrow',
            'bonus_code' => 0,
            'tag' => 'sb'
        ]
    ];

    /**
     * Do the migration
     */

    public function up()
    {
        foreach ($this->languageStrings as $languageString) {
            $this->connection
                ->table($this->table)
                ->insert($languageString);
        }
        foreach ($this->languageConnections as $languageConnection) {
            $this->connection
                ->table($this->tableConnections)
                ->insert($languageConnection);
        }
    }
    /**
     * Undo the migration
     */
    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->languageStrings as $languageString) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $languageString['alias'])
                ->where('language', '=', $languageString['language'])
                ->delete();
        }
        foreach ($this->languageConnections as $languageConnection) {
            $this->connection
                ->table($this->tableConnections)
                ->where('target_alias', '=', $languageConnection['target_alias'])
                ->where('tag', '=', $languageConnection['tag'])
                ->delete();
        }

    }
}
