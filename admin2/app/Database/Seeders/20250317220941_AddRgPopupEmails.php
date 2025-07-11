<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddRgPopupEmails extends Seeder
{

    private string $mailTable;
    private \App\Extensions\Database\Connection\Connection $connection;
    /**
     * @var array
     */
    private array $rgIds;

    public function init()
    {
        $this->mailTable = 'mails';
        $this->connection = DB::getMasterConnection();
        $this->rgIds = [5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16, 18, 19, 20, 21, 24, 25, 27, 28, 29, 30, 31, 32, 33, 34,
            35, 37, 38, 39, 58, 59, 62, 63, 66, 68, 70, 72, 73, 74, 75, 76];
    }

    public function up()
    {
        foreach ($this->rgIds as $id) {
            $this->connection
                ->table($this->mailTable)
                ->insert([
                    'mail_trigger' => "RG{$id}.popup.ignored",
                    'subject' => "mail.RG{$id}.popup.ignored.subject",
                    'content' => "mail.RG{$id}.popup.ignored.content",
                ]);
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'LIKE', '%popup.ignored%')
            ->delete();
    }
}