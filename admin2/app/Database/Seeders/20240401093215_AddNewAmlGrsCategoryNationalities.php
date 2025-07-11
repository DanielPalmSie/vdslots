<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddNewAmlGrsCategoryNationalities extends Seeder
{
    private string $table;
    private string $category_slug;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'nationalities';
    }

    public function up()
    {
        $insert = [];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $bank_countries = phive('Localizer')->getAllBankCountries('iso');

        foreach ($country_jurisdiction_map as $jurisdiction) {
            // parent category
            $insert[] = [
                'name' => $this->category_slug,
                'jurisdiction' => $jurisdiction,
                'title' => 'Nationalities',
                'score' => 0,
                'type' => 'option',
                'category' => '',
                'section' => 'AML',
                'data' => '',
            ];
            foreach ($bank_countries as $country) {
                // category score settings
                $insert[] = [
                    'title' => $country['name'],
                    'name' => $country['iso'],
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
                ->whereIn('category', [$this->category_slug])
                ->orWhereIn('name', [$this->category_slug])
                ->delete();
        }, true);
    }
}