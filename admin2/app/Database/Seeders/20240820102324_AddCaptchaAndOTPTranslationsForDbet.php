<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddCaptchaAndOTPTranslationsForDbet extends Seeder
{
    private string $table;
    private Connection $connection;
    private string $brand;
    private array $data;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->data = [
            ['alias' => 'otp.description', 'value' => 'Enter the <span>One Time Password (OTP)</span> that has been sent to your phone number ending in {{phone_last_3}} and to your e-mail address.'],
            ['alias' => 'otp.popup.top.html', 'value' => '<img src="/diamondbet/images/dbet/king_captcha.png" /><h4>One Time Password</h4>'],
            ['alias' => 'registration.popup.captcha.placeholder', 'value' => 'Insert Captcha Code'],
            ['alias' => 'registration.popup.captcha.reset', 'value' => 'Reset'],
            ['alias' => 'registration.popup.captcha.label', 'value' => 'Enter Captcha to Proceed']
        ];
    }

    public function up()
    {
        if (strtolower($this->brand) !== 'dbet') {
            return;
        }

        foreach ($this->data as $row) {
            $existing_row = $this->connection->table($this->table)
                ->where('alias', $row['alias'])
                ->where('language', 'en')
                ->first();

            if($existing_row) {
                $this->connection->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', 'en')
                    ->update(['value' => $row['value']]);
            } else {
                $this->connection->table($this->table)
                    ->insert([
                        'alias' => $row['alias'],
                        'language' => 'en',
                        'value' => $row['value']
                    ]);
            }
        }
    }
}
