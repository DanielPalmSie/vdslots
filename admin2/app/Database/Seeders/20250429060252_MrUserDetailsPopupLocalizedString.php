<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class MrUserDetailsPopupLocalizedString extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'paynplay.user-details.popup-title' => 'Welcome to Megariches!',
            'paynplay.user-details.more-details' => 'Before you can proceed to gameplay we require you to provide some more details.',
            'paynplay.user-details.wish-to-receive-offers' => 'I wish to receive all kind of free spins and promotional offers via all channels (email, SMS, phone and post).',
            'paynplay.user-details.email-placeholder' => 'Email',
            'paynplay.user-details.phone-placeholder' => 'Mobile Number',
            'paynplay.user-details.continue' => 'Continue',
            'paynplay.user-details.invalid-email' => 'Please enter a valid email address',
            'paynplay.user-details.invalid-phone' => 'Please enter a valid mobile number',
            'paynplay.user-details.invalid-country' => 'Please select a country',

            'paynplay.account-verification.popup-title' => 'Account Verification',
            'paynplay.account-verification.enter-code' => 'Enter the 4 digit code the was sent to:',
            'paynplay.account-verification.email' => 'Email:',
            'paynplay.account-verification.mobile' => 'Mobile:',
            'paynplay.account-verification.change-email-mobile' => 'Change email/mobile',
            'paynplay.account-verification.code-placeholder' => 'Validation Code',
            'paynplay.account-verification.resend-code' => 'Resend Code',
            'paynplay.account-verification.validate' => 'Validate',
            'paynplay.account-verification.invalid-code' => 'Invalid validation code',
        ]
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

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->upsert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ], ['alias', 'language']);
            }
        }
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }
    }
}
