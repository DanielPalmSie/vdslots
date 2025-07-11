<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class InactivePlayersEmailTrigger extends Migration
{

    protected $table;

    private $connection;

    public function init()
    {
        $this->table = 'mails';
        $this->connection = DB::getMasterConnection();
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)->insert([
                'mail_trigger' => 'inactive_players_email',
                'subject' => 'mail.inactive_players_email.subject',
                'content' => 'mail.inactive_players_email.content'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('mail_trigger', '=', 'inactive_players_email')
            ->where('subject', '=', 'mail.inactive_players_email.subject')
            ->where('content', '=', 'mail.inactive_players_email.content')
            ->delete();
    }
}
