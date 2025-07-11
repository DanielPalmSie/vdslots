<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForPrivacyConfirmationPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'privacy.dashboard.confirmation.message.popup',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/privacy-confirmation.png"><div>Do you wish to receive free spins and bonus offers via all our marketing channels?</div></div>',
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
            // removing it for all languages before updating the data
            $this->connection
                ->table($this->table)
                ->where('alias','privacy.dashboard.confirmation.message.popup')
                ->where('language', 'en')
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
                ->where('alias','privacy.dashboard.confirmation.message.popup')
                ->where('language', 'en')
                ->delete();
        }
    }
}
