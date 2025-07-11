<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddWeekendBoosterEmailTrigger extends Seeder
{
    private string $table = 'mails';
    private Connection $connection;

    private array $data = [
        'mail_trigger' => 'transtype.101.approved',
        'subject' => 'mail.transtype.101.approved.subject',
        'content' => 'mail.transtype.101.approved.content',
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $exists = $this->connection
            ->table($this->table)
            ->where('mail_trigger', $this->data['mail_trigger'])
            ->first();

        if (empty($exists)) {
            $this->connection->table($this->table)->insert($this->data);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('mail_trigger', '=', $this->data['mail_trigger'])
            ->delete();
    }
}