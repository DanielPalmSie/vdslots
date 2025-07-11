
<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class MegarichesSGAErrorMessages extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;


    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.error.login-success.title' => 'LOGIN',
            'paynplay.error.login-success.sub-title' => 'SUCCESS',
            'paynplay.error.login-success.description' => 'Logged in successfully',
            'paynplay.error-popup.proceed' => 'OK',
            'paynplay.deposit.unknown.failure.description' => 'We’re sorry, but an unexpected error has occurred during the deposit process.                            Please try again later. If the problem persists, please contact our customer service at support@kungaslottet.se for more information.'
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
