<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class EditConfigChangeMailTrigger extends Seeder
{

    protected $mailTable;
    protected $localizedStringTable;
    protected $connection;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'mail.config.change.subject',
            'value' => 'New config change been Edited',
        ],
        [
            'language' => 'en',
            'alias' => 'mail.config.change.content',
            'value' => '

                    <p><i>New config change:</i></p>
                     <p> <i>Change timestamp: _TIMESTAMP_ </i></p>
                     <p> <i>Change made by: __MADE-BY__ </i></p>
                    <p>
                        &nbsp;</p>
                    <table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
    <tbody>
        <tr>
            <td style="text-align: center;">
                <em><strong>Value Name</strong></em>
            </td>
            <td style="text-align: center;">
                <em><strong>From</strong></em>
            </td>
            <td style="text-align: center;">
                <em><strong>To</strong></em>
            </td>
        </tr>

        <tr>
            <td style="text-align: center;">
                <em><strong>Config name</strong></em>
            </td>
            <td style="text-align: center;">
                __OLD-CONFIG-NAME__
            </td>
            <td style="text-align: center;">
                __NEW-CONFIG-NAME__
            </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <i><b>Config tag</b></i>
            </td>
            <td style="text-align: center;">
                __OLD-CONFIG-TAG__
            </td>
            <td style="text-align: center;">
                __NEW-CONFIG-TAG__
            </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <i><b>Config value</b></i>
            </td>
            <td style="text-align: center;">
                __OLD-CONFIG-VALUE__
            </td>
            <td style="text-align: center;">
                __NEW-CONFIG-VALUE__
            </td>
        </tr>
    </tbody>
</table>
         <br />
                        Thanks</p>
   '
        ]
    ];
    public function init()
    {
        $this->mailTable = 'mails';
        $this->localizedStringTable = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {

        $this->connection
            ->table($this->mailTable)
            ->insert([
                'mail_trigger' => 'config.changes',
                'subject' => 'mail.config.change.subject',
                'content' => 'mail.config.change.content'
            ]);

        $this->connection
            ->table($this->localizedStringTable)
            ->insert($this->data);
    }
    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'config.changes')
            ->delete();

        $this->connection
            ->table($this->localizedStringTable)
            ->whereIn('alias', ['mail.config.change.subject',
                'mail.config.change.content'])
            ->delete();
    }
}
