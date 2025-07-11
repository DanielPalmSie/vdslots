<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForBankIdRegistrationPopup extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'bankid.registration.form.title' => 'Welcome to Dbet',
            'bankid.registration.infotext' => 'Before you can proceed to gameplay we require you to provide some more details.',
            'bankid.registration.iam18orolder' => 'I confirm that I am 18 years or older',
            'bankid.registration.optinpromotions' => 'I wish to opt-in for marketing communications',
            'bankid.registration.invalid-mobile-number' => 'Please enter a valid mobile number'
        ],
        'sv' => [
            'bankid.registration.form.title' => 'Välkomen till Dbet',
            'bankid.registration.infotext' => 'Innan du kan fortsätta till spel måste du tillhandahålla lite mer information.',
            'bankid.registration.iam18orolder' => 'Jag bekräftar att jag är 18 år eller äldre',
            'bankid.registration.optinpromotions' => 'Jag vill opt-in för marknadsförings kommunikation',
            'bankid.registration.invalid-mobile-number' => 'Ange ett giltligt mobilnummer'
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->insert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ]);
            }
        }
    }
    
    public function down()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }           
    }           
    
}