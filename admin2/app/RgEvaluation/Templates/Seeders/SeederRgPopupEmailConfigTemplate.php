<?= "<?php ";?>

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class <?= $className ?> extends Seeder
{
    private string $mailTable;
    private Connection $connection;
    private string $triggerName = "<?= $this->container['trigger_name'] ?>";

    public function init()
    {
        $this->mailTable = 'mails';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->mailTable)
            ->insert([
                'mail_trigger' => "{$this->triggerName}.popup.ignored",
                'subject' => "mail.{$this->triggerName}.popup.ignored.subject",
                'content' => "mail.{$this->triggerName}.popup.ignored.content",
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'LIKE', "%{$this->triggerName}.popup.ignored%")
            ->delete();
    }
}
