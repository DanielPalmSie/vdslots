<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddingLocalizedStringForDocuments extends Seeder
{
    private $connection;
    private string $brand;
    private string $table = 'localized_strings';

    private array $data = [
    'restrict.msg.expired.documents.title' => 'Re-Verify Documents',
    'restrict.msg.expired.documents.btn' => 'Ok',

    'restrict.msg.verify.documents' => '<div>
                    <img class="popup-v2-img"
                         src="/diamondbet/images/megariches/document_verification.svg">
                    <div>
                        <p>
                            We are required to verify your identity and proof of address before you can make a deposit and play.
                            To complete the verification process, please go to your <a style="text-decoration: underline;" href="{{phive|UserHandler|getUserAccountUrl|documents}}" target="_top">Documents</a> page and upload the requested documents.
                            Please contact our Customer Service via live chat or e-mail <span class="customer-support-link">(support@megariches.com)</span> if you have any further questions.
                        </p>
                    </div>
                </div>'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }
        $this->updateData($this->data);
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }
        $this->removeData($this->data);
    }

    function updateData(array $data) {
        foreach ($data as $alias => $value) {

            $exists =  $this->connection
                ->table($this->table)
                ->where('alias', $alias)
                ->where('language', 'en')
                ->exists();

            if($exists) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', 'en')
                    ->update(['value' => $value]);
            }else {
                $this->connection
                    ->table($this->table)
                    ->insert([
                        'language' => 'en',
                        'alias' => $alias,
                        'value' => $value
                    ]);
            }
        }
    }

    function removeData(array $data) {
        foreach ($data as $alias => $value) {
            $this->connection
                ->table($this->table)
                ->where('alias', $alias)
                ->where('value', $value)
                ->where('language', 'en')
                ->delete();
        }
    }
}

