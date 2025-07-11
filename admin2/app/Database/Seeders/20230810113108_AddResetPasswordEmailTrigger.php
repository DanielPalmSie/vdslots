<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddResetPasswordEmailTrigger extends Seeder
{
    private string $table = 'mails';
    private Connection $connection;

    private array $string = [
        'mail_trigger' => 'password-reminder-change',
        'subject' => 'mail.password-reminder-change.subject',
        'content' => 'mail.password-reminder-change.content',
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
            ->where('mail_trigger', $string['mail_trigger'])
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
            ->where('alias', '=', $this->string['alias'])
            ->where('language', '=', $this->string['language'])
            ->update([
                'value' => $this->string['cur_value']
            ]);
    }
}
