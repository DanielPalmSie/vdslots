<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;

class UpdateStringContentForMegarichesPromotion extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $new_data = [
        'en' => [
            'page.main.description' => 'Mega Riches Casino - Play over 8,000 casino games and slots online! Get 50 chances to become a millionaire and a 100% bonus up to {{csym}} {{modm:25}} on your first deposit.',
            'page.casino-games.description' => 'Play online casino at Mega Riches. All new players get 50 chances to become a millionaire and a 100% bonus up to {{csym}} {{modm:25}} on your first deposit.',
        ]
    ];

    protected array $old_data = [
        'en' => [
            'page.main.description' => 'Mega Riches Casino - Play over 8,000 casino games and slots online! Get 50 chances to become a millionaire and a 100% bonus up to {{csym}} {{modm:100}} on your first deposit.',
            'page.casino-games.description' => 'Play online casino at Mega Riches. All new players get 50 chances to become a millionaire and a 100% bonus up to {{csym}} {{modm:100}} on your first deposit.',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            foreach ($this->new_data as $lang => $translation) {
                foreach ($translation as $alias => $value) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $lang)
                        ->update(['value' => $value]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            foreach ($this->old_data as $lang => $translation) {
                foreach ($translation as $alias => $value) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $lang)
                        ->update(['value' => $value]);
                }
            }
        }
    }
}
