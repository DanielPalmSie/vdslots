<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddBoxAttributeChangeEmail extends Seeder
{
    protected $mailTable;
    protected $localizedStringTable;
    protected $connection;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'mail.box-attribute.change.subject',
            'value' => 'New box attribute change',
        ],
        [
            'language' => 'en',
            'alias' => 'mail.box-attribute.change.content',
            'value' => '
                <p><i>New Box Attribute change:</i></p>
                <p><i>Change timestamp: __TIMESTAMP__</i></p>
                <p><i>Change made by: __MADE-BY__</i></p>
                <p>&nbsp;</p>
                <table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
                    <tbody>
                        <tr>
                            <td style="text-align: center;">
                                <em><strong>Box Attribute Name</strong></em>
                            </td>
                            <td style="text-align: center;">
                                <em><strong>Box Name</strong></em>
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
                                <i><b>__NAME__</b></i>
                            </td>
                            <td style="text-align: center;">
                                __BOX-NAME__
                            </td>
                            <td style="text-align: center;">
                                __OLD-VALUE__
                            </td>
                            <td style="text-align: center;">
                                __NEW-VALUE__
                            </td>
                        </tr>
                    </tbody>
                </table>
                <br />
                <p>Thanks</p>
            ',
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
                'mail_trigger' => 'box-attribute.change',
                'subject' => 'mail.box-attribute.change.subject',
                'content' => 'mail.box-attribute.change.content'
            ]);

        $this->connection
            ->table($this->localizedStringTable)
            ->insert($this->data);
    }

    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'box-attribute.change')
            ->delete();

        $this->connection
            ->table($this->localizedStringTable)
            ->whereIn('alias', [
                'mail.box-attribute.change.subject',
                'mail.box-attribute.change.content'
            ])
            ->delete();
    }
}
