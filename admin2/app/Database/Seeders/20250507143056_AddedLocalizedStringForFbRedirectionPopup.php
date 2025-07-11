<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddedLocalizedStringForFbRedirectionPopup extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'fb-popup-sub-title' => 'Open in Your Browser',
            'fb-popup-btn' => 'Open in Browser',
            'fb-popup-description' => 'For the best experience, please open this page in your device\'s default browser',
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
                $this->connection->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}
