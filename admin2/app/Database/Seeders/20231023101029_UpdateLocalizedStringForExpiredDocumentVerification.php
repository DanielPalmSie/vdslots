<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class UpdateLocalizedStringForExpiredDocumentVerification extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'restrict.msg.verify.documents',
            'value' => '<div>
                    <img class="popup-v2-img"
                         src="/diamondbet/images/kungaslottet/document_verification.svg">
                    <h6 class="popup-v2-subtitle">Verification of Identity</h6>
                    <div>
                        <p>
                            Your current documentation needs to be updated, this could be because your identity documents have expired or you have made a change to your personal details.
                            <br/>
                            Please go to your <a style="text-decoration: underline;" href="{{phive|UserHandler|getUserAccountUrl|documents}}" target="_top">Documents</a> page and upload the requested documents to complete the verification process. You will need to complete the verification process before you can continue to withdraw.
                            <br/>
                            Please contact our Customer Service via live chat or e-mail (<strong>support@videoslots.com</strong>) if you have any further questions.

                        </p>
                    </div>
                </div>'
        ],
        [
            'language' => 'en',
            'alias' => 'restrict.msg.expired.documents.title',
            'value' => 'Message',
        ],
        [
            'language' => 'en',
            'alias' => 'restrict.msg.expired.documents.btn',
            'value' => 'Verify'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias',
                    [
                        'restrict.msg.verify.documents',
                        'restrict.msg.expired.documents.title',
                        'restrict.msg.expired.documents.btn'
                    ]
                )
                ->delete();

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias',
                    [
                        'restrict.msg.verify.documents',
                        'restrict.msg.expired.documents.title',
                        'restrict.msg.expired.documents.btn'
                    ]
                )
                ->where('language', 'en')
                ->delete();
        }
    }
}
