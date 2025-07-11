<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForOTPonLoginKungaMga extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;
    protected array $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->data = [];
    }

    public function up()
    {
        switch ($this->brand) {
            case 'kungaslottet':
                $this->data = [
                    [
                        'language' => 'en',
                        'alias' => 'otp.popup.top.html',
                        'value' => '<div class="otp-top"><img src="/diamondbet/images/kungaslottet/king_captcha.png"><p>One Time Password</p></div>',
                    ],
                    [
                        'language' => 'en',
                        'alias' => 'otp.description',
                        'value' => 'Enter the <strong>One Time Password (OTP)</strong> that has been sent to your phone number ending in {{phone_last_3}} and to your e-mail address.',
                    ],
                    [
                        'language' => 'en',
                        'alias' => 'login.otp',
                        'value' => 'Password'
                    ],
                    [
                        'language' => 'en',
                        'alias' => 'not.received.otp',
                        'value' => 'Did not receive OTP?'
                    ]
                ];

                // Delete existing data for kungaslottet brand
                $this->connection
                    ->table($this->table)
                    ->whereIn('alias',['otp.popup.top.html', 'otp.description', 'login.otp', 'not.received.otp'])
                    ->where('language', 'en')
                    ->delete();
                break;
            case 'mrvegas':
            case 'videoslots':
                // Insert only 'otp.popup.top.html' for mrvegas and videoslots
                $this->data[] = [
                    'language' => 'en', 
                    'alias' => 'otp.popup.top.html',
                    'value' => '<div></div>',
                ];
                break;
        }

        // Insert data into the database
        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        switch ($this->brand) {
            case 'kungaslottet':
                // Delete data for kungaslottet brand
                $this->connection
                    ->table($this->table)
                    ->whereIn('alias',['otp.popup.top.html', 'otp.description', 'login.otp', 'not.received.otp'])
                    ->where('language', 'en')
                    ->delete();
                break;
            case 'mrvegas':
            case 'videoslots':
                // Delete only 'otp.popup.top.html' for mrvegas and videoslots
                $this->connection
                    ->table($this->table)
                    ->where('alias','otp.popup.top.html')
                    ->where('language', 'en')
                    ->delete();
                break;
        }
    }
}