<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddRiskProfileRatingSection extends Seeder
{

    public function init()
    {
        $this->table = 'risk_profile_rating';
    }

    public function up()
    {
        $insert = [];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $sections = [
            'RG' => [
                ['title' => 'Social Gambler', 'score' => '59'],
                ['title' => 'Low Risk', 'score' => '69'],
                ['title' => 'Medium Risk', 'score' => '79'],
                ['title' => 'High Risk', 'score' => '100'],
            ],
            'AML' => [
                ['title' => 'Social Gambler', 'score' => '5'],
                ['title' => 'Low Risk', 'score' => '6'],
                ['title' => 'Medium Risk', 'score' => '7'],
                ['title' => 'High Risk', 'score' => '10'],
            ],
        ];
        foreach ($country_jurisdiction_map as $jurisdiction) {
            foreach ($sections as $section => $settings) {
                $insert[] = [
                    'name' => 'rating_score',
                    'jurisdiction' => $jurisdiction,
                    'title' => "{$section} Risk Profile Rating",
                    'score' => 0,
                    'type' => 'interval',
                    'category' => '',
                    'section' => $section,
                ];
                foreach ($settings as $setting) {
                    $insert[] = [
                        'name' => $setting['title'],
                        'jurisdiction' => $jurisdiction,
                        'title' => $setting['title'],
                        'score' => $setting['score'],
                        'type' => '',
                        'category' => 'rating_score',
                        'section' => $section,
                    ];
                }
            }
        }
        DB::loopNodes(function ($connection) use ($insert) {
            $connection->table($this->table)
                ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section'], ['score']);
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', 'rating_score')
                ->orWhere('name', 'rating_score')
                ->delete();
        }, true);
    }
}