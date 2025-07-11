<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class GenerateLinkVerificationEmailTemplate extends Seeder
{

    protected $mailTable;
    protected $localizedStringTable;
    protected $connection;
    private $brand;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'mail.page.link.verification.subject',
            'value' => 'Action Required: Issue encountered with daily link checks for Italy ADM',
        ],
        [
            'language' => 'en',
            'alias' => 'mail.page.link.verification.content',
            'value' => '<p>
                        <i>Dear Team,</i></p>
                    <p>
                        <i>Our daily link check found issues with some links, details as per below:</i></p>
                    <p>
                        &nbsp;</p>
                    <table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
                        <tbody>
                            <tr>
                                <td style="text-align: center;">
                                    <em><strong>Report&nbsp; timestamp</strong></em></td>
                                <td style="text-align: center;">
                                    __REPORT-TIME__&nbsp; &nbsp; &nbsp;</td>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <em><strong>Brand</strong></em></td>
                                <td style="text-align: center;">
                                    &nbsp;__BRAND__</td>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <i><b>Production page:</b></i></td>
                                <td style="text-align: center;">
                                    __PRODUCTION-PAGE__</td>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <i><b>URL:</b></i></td>
                                <td style="text-align: center;">
                                    __BROKEN-LINKS__</td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
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
        $this->brand = phive('BrandedConfig')->getBrand();

    }



    /**
     * Do the migration
     */
    public function up()
    {

        if ($this->brand === 'videoslots') {
            $this->connection
                ->table($this->mailTable)
                ->insert([
                   'mail_trigger' => 'page.link.verification',
                   'subject' => 'mail.page.link.verification.subject',
                   'content' => 'mail.page.link.verification.content'
                ]);

            $this->connection
                ->table($this->localizedStringTable)
                ->insert($this->data);
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand === 'videoslots') {
            $this->connection
                ->table($this->mailTable)
                ->where('mail_trigger', 'page.link.verification')
                ->delete();

            $this->connection
                ->table($this->localizedStringTable)
                ->whereIn('alias', ['mail.page.link.verification.subject',
                    'mail.page.link.verification.content'])
                ->delete();
        }
    }
}
