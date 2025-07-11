<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateLiveEventNoteValueInLocalizedStrings extends Migration
{
    /** @var string */
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.footer-content-notice-header.html')
            ->where('language', '=', 'en')
            ->update(['value' => '<p>Please note that live betting information is subject to a time delay and may not be accurate.</p>']);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.footer-content-notice-header.html')
            ->where('language', '=', 'en')
            ->update(['value' => 'Please note that live betting information is subject to a time delay and may not be accurate.']);

    }
}
