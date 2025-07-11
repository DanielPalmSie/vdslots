<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddConfigChangeMailTrigger extends Seeder
{

    protected $mailTable;
    protected $localizedStringTable;
    protected $connection;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'mail.config.add.subject',
            'value' => 'New config been Added',
        ],
        [
            'language' => 'en',
            'alias' => 'mail.config.add.content',
            'value' => '
                    <p><i>New Config added:</i></p>
                    <p> <i>Change timestamp: _TIMESTAMP_ </i></p>
                     <p> <i>Change made by: __MADE-BY__ </i></p>
                    <p>
                        &nbsp;</p>
                    <table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
    <tbody>
        <tr>
        </tr>

        <tr>
            <td style="text-align: center;">
                <em><strong>Config name</strong></em>
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
                 __NEW-CONFIG-TAG__
            </td>

        </tr>
         <tr>
            <td style="text-align: center;">
                <i><b>Config type</b></i>
            </td>
            <td style="text-align: center;">
                __NEW-CONFIG-TYPE__
            </td>

        </tr>
    </tbody>
</table>
                    <p>
                        <br />
                        Thanks</p> '
        ]
    ];

    public function init()
    {
        $this->mailTable = 'mails';
        $this->localizedStringTable = 'localized_strings';
        $this->connection = DB::getMasterConnection();

    }



    /**
     * Do the migration
     */
    public function up()
    {

        $this->connection
            ->table($this->mailTable)
            ->insert([
                'mail_trigger' => 'config.add',
                'subject' => 'mail.config.add.subject',
                'content' => 'mail.config.add.content'
            ]);

        $this->connection
            ->table($this->localizedStringTable)
            ->insert($this->data);

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'config.add')
            ->delete();

        $this->connection
            ->table($this->localizedStringTable)
            ->whereIn('alias', ['mail.config.add.subject',
                'mail.config.add.content'])
            ->delete();
    }
}
