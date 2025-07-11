<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ChangeAuditMailTriggers extends Seeder
{
    protected $mailTable;

    protected $connection;

    public function init()
    {
        $this->mailTable = 'mails';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'config.changes')
            ->update([
                'mail_trigger' => 'config.change'
            ]);

        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'bonus-type.changes')
            ->update([
                'mail_trigger' => 'bonus-type.change'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'config.change')
            ->update([
                'mail_trigger' => 'config.changes'
            ]);

        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'bonus-type.change')
            ->update([
                'mail_trigger' => 'bonus-type.changes'
            ]);
    }
}
