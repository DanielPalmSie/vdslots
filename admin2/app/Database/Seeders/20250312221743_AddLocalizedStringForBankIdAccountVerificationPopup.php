<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForBankIdAccountVerificationPopup extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'account-verification.title' => 'Account Verification',
            'account-verification.instructions' => 'Enter 4 digit code that was sent to:',
            'account-verification.email' => 'Email',
            'account-verification.mobile' => 'Mobile',
            'account-verification.change-link' => 'Change email/Mobile',
            'account-verification.validation-code' => 'Validation Code',
            'account-verification.validate' => 'Validate',
            'account-verification.resend-code' => 'Resend Code',
        ],
        'sv' => [
            'account-verification.title' => 'Kontoverifiering',
            'account-verification.instructions' => 'Skriv in 4 siffror som skickades till:',
            'account-verification.email' => 'E-post',
            'account-verification.mobile' => 'Mobil',
            'account-verification.change-link' => 'Ändra e-post/Mobil',
            'account-verification.validation-code' => 'Valideringskod',
            'account-verification.validate' => 'Bekräfta',
            'account-verification.resend-code' => 'Skicka igen',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up(): void
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $insertData = [];
        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $insertData[] = [
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language
                ];
            }
        }

        $this->connection->table($this->table)->upsert(
            $insertData,
            ['alias', 'language'],
            ['value']
        );
    }

    public function down(): void
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        $aliases = [];
        foreach ($this->data as $translations) {
            $aliases = array_merge($aliases, array_keys($translations));
        }
        $aliases = array_unique($aliases);


        $this->connection->table($this->table)
            ->whereIn('alias', $aliases)
            ->whereIn('language', array_keys($this->data)) 
            ->delete();
    }
    
}