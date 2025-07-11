<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class liveEventNoteToLocalizedStrings extends Migration
{
    /** @var string */
    protected $table;

    protected $connection;

    private $langStrings = [
        [
            'alias' => 'sb.footer-content-notice-header.html',
            'language' => 'en',
            'value' => 'Please note that live betting information is subject to a time delay and may not be accurate.'
        ],
        [
            'alias' => 'sb.footer-content-notice-body.html',
            'language' => 'en',
            'value' => '<p>Videoslots Sports will do its utmost to ensure that information displayed on our site is correct.
                            However, please be aware such information should only be used as a guide.
                            This includes any information about an event, such as the current score, progression and
                            how much time remains before the match is completed. Videoslots Sports does not assume any liability
                                if any such information is incorrect. Please refer to our general terms and conditions for more information
                                on how betting markets are settled.</p>
                                <p>For the purposes of Live betting, customers must be aware any information shown on Videoslots Sports
                                may be subject to delays. The extent of any delay may vary between different sports, competitions and/or
                                events, and may also vary from customer to customer depending on the device they are receiving pictures or data.</p>'
        ],
    ];

    private $langStringConnections = [
        [
            'target_alias' => 'sb.footer-content-notice-header.html',
            'bonus_code' => 0,
            'tag' => 'sb.footer-content',
        ],
        [
            'target_alias' => 'sb.footer-content-notice-body.html',
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
