<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForEmailValidationTranslations extends Seeder
{

    private string $table = 'localized_strings';
    private Connection $connection;
    protected array $data = [
        'en' => [
            'register.email.already.registered' => 'The email address is already registered. Please use a different email address.',
            'register.email.error.message' => "Enter a valid email address with an '@' symbol and a domain (e.g., user@example.com).",
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {

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
        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }
    }
}