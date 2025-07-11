<?php

use App\Extensions\Database\Connection\Connection;
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;


class AddRG60MailTrigger extends Migration
{
    private string $table = 'mails';
    private Connection $connection;

    private array $string = [
        'mail_trigger' => 'request-soi-to-increase-ndl',
        'subject' => 'mail.request-soi-to-increase-ndl.subject',
        'content' => 'mail.request-soi-to-increase-ndl.content',
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
            ->where('mail_trigger', $this->string['mail_trigger'])
            ->first();

        if (empty($exists)) {
            $this->connection->table($this->table)->insert($this->string);
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('mail_trigger', '=', $this->string['mail_trigger'])
            ->delete();
    }
}
