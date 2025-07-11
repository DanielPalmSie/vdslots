<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForDbetRegistrationStep1 extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'register.step1.top',
            'value' => 'Welcome to Dbet',
        ],
        [
            'language' => 'en',
            'alias' => 'register.step1.infoheader',
            'value' => 'Become a member at Dbet.com'
        ],
        [
            'language' => 'en',
            'alias' => 'register.privacy1',
            'value' => 'I agree to Dbet'
        ],
        [
            'language' => 'en',
            'alias' => 'register.toc1',
            'value' => 'I accept Dbet'
        ],
        [
            'language' => 'en',
            'alias' => 'register.opt.in.promotions',
            'value' => 'I wish to receive all kind of marketing material'
        ],
        [
            'language' => 'en',
            'alias' => 'register.opt.in.promotions.details',
            'value' => '(email, SMS, phone and post)'
        ],
        [
            'language' => 'en',
            'alias' => 'registration.with.verification.method',
            'value' => 'Register with BankID'
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        foreach ($this->data as $item) {
            $this->connection
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->update(['value' => $item['value']]);
        }
    }
}
