<?php 

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class PopulateAmlGrsScores extends Seeder
{
    private string $table;
    private string $category_slug;
    private array $countryJurisdictionMap;
    private array $paymentMethods;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'deposit_method';
        $this->countryJurisdictionMap = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $this->paymentMethods = [
            'astropay' => [
                'title' => 'AstroPay',
                'score' => 60,
            ],
            'cashtocode' => [
                'title' => 'CashtoCode',
                'score' => 100,
            ],
            'euteller' => [
                'title' => 'Euteller',
                'score' => 60,
            ],
            'flykk' => [
                'title' => 'Flykk',
                'score' => 60,
            ],
            'mifinity' => [
                'title' => 'MiFinity',
                'score' => 60,
            ],
            'mobilepay' => [
                'title' => 'MobilePay',
                'score' => 40,
            ],
            'payretailers' => [
                'title' => 'PayRetailers',
                'score' => 60,
            ],
            'muchbetter' => [
                'title' => 'MuchBetter',
                'score' => 60,
            ],
        ];
    }

    public function up()
    {
        $insert = [];

        foreach ($this->countryJurisdictionMap as $jurisdiction) {
            foreach ($this->paymentMethods as $key => $method) {
                $insert[] = [
                    'title' => $method['title'],
                    'name' => $key,
                    'jurisdiction' => $jurisdiction,
                    'score' => $method['score'],
                    'type' => '',
                    'category' => $this->category_slug,
                    'section' => 'AML',
                    'data' => '',
                ];
            }
            DB::loopNodes(function ($connection) use ($insert) {
                $connection->table($this->table)
                    ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);

            }, true);
        }
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        $insert = [];

        foreach ($this->countryJurisdictionMap as $jurisdiction) {
            foreach ($this->paymentMethods as $key => $method) {
                $insert[] = [
                    'title' => $method['title'],
                    'name' => $key,
                    'jurisdiction' => $jurisdiction,
                    'score' => 0,
                    'type' => '',
                    'category' => $this->category_slug,
                    'section' => 'AML',
                    'data' => '',
                ];
            }
            DB::loopNodes(function ($connection) use ($insert) {
                $connection->table($this->table)
                    ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);

            }, true);
        }
    }
}