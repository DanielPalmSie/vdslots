<?php 

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class BAN11193AddNewAmlGrsPaymentMethods extends Seeder
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
            'muchbetter' => [
                'title' => 'MuchBetter',
            ],
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

        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', $this->category_slug)
                ->whereIn('name', [
                    'payanybank',
                ])
                ->delete();
        }, true);
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
                    'muchbetter',
                ])
                ->delete();
        }, true);

        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        foreach ($country_jurisdiction_map as $jurisdiction) {
            $insert[] = [
                'title' => 'PayAnyBank',
                'name' => 'payanybank',
                'jurisdiction' => $jurisdiction,
                'score' => 0,
                'type' => '',
                'category' => $this->category_slug,
                'section' => 'AML',
                'data' => '',
            ];
            DB::loopNodes(function ($connection) use ($insert) {
                $connection->table($this->table)
                    ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);

            }, true);
        }
    }
}