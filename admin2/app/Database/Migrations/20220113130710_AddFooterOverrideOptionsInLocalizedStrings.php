<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddFooterOverrideOptionsInLocalizedStrings extends Migration
{
    /** @var string */
    protected $table;
    protected $tableConnections;

    /** @var Connection */
    protected $connection;

    private $langStrings = [
        [
            'alias' => 'sb.footer-prematch-content.html',
            'language' => 'en',
            'value' => ''
        ],
        [
            'alias' => 'sb.footer-live-content.html',
            'language' => 'en',
            'value' => ''
        ],
    ];

    private $langStringConnections = [
        [
            'target_alias' => 'sb.footer-prematch-content.html',
            'bonus_code' => 0,
            'tag' => 'sb.footer-content',
        ],
        [
            'target_alias' => 'sb.footer-live-content.html',
            'bonus_code' => 0,
            'tag' => 'sb.footer-content',
        ],
    ];

    public function init()
    {
        $this->table = 'localized_strings';
        $this->tableConnections = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->langStrings as $langString) {
            $this->connection
                ->table($this->table)
                ->insert($langString);
        }
        foreach ($this->langStringConnections as $langStringsConnection) {
            $this->connection
                ->table($this->tableConnections)
                ->insert($langStringsConnection);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->langStrings as $langString) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $langString['alias'])
                ->where('language', '=', $langString['language'])
                ->delete();
        }
        foreach ($this->langStringConnections as $langStringsConnection) {
            $this->connection
                ->table($this->tableConnections)
                ->where('target_alias', '=', $langStringsConnection['target_alias'])
                ->where('tag', '=', $langStringsConnection['tag'])
                ->delete();
        }

    }

}

