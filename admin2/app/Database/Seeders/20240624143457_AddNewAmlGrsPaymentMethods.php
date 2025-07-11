<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddNewAmlGrsPaymentMethods extends Seeder
{
    private string $table;
    private string $category_slug;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'deposit_method';
    }

    public function up()
    {
        $insert = [];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $newPaymentMethods = [
            'mobilepay' => [
                'title' => 'MobilePay',
            ],
            'bambora' => [
                'title' => 'Bambora',
            ],
            'astropay' => [
                'title' => 'AstroPay',
            ],
            'cashtocode' => [
                'title' => 'CashtoCode',
            ],
            'euteller' => [
                'title' => 'Euteller',
            ],
            'flykk' => [
                'title' => 'Flykk',
            ],
            'mifinity' => [
                'title' => 'MiFinity',
            ],
            'payanybank' => [
                'title' => 'PayAnyBank',
            ],
            'payretailers' => [
                'title' => 'PayRetailers',
            ]
        ];

        foreach ($country_jurisdiction_map as $jurisdiction) {
            foreach ($newPaymentMethods as $key => $method) {
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

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', $this->category_slug)
                ->whereIn('name', [
                    'mobilepay',
                    'bambora',
                    'astropay',
                    'cashtocode',
                    'euteller',
                    'flykk',
                    'mifinity',
                    'payanybank',
                    'payretailers'
                ])
                ->delete();
        }, true);
    }
}