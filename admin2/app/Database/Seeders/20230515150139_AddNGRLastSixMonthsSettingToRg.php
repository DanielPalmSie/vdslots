<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddNGRLastSixMonthsSettingToRg extends Seeder
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
                ['title' => 'NGR last _MONTHS months', 'slug' => 'ngr_last_6_months', 'score' => 0],
            ],
        ];
        foreach ($country_jurisdiction_map as $jurisdiction) {
            foreach ($sections as $section => $settings) {
                foreach ($settings as $setting) {
                    // parent category
                    $insert[] = [
                        'name' => $setting['slug'],
                        'jurisdiction' => $jurisdiction,
                        'title' => $setting['title'],
                        'score' => 0,
                        'type' => 'interval',
                        'category' => "",
                        'section' => $section,
                        'data' => json_encode([
                            "replacers" => [
                                "_MONTHS" => 6
                            ]
                        ]),
                    ];
                    // children
                    $insert[] = [
                        'name' => "0,0",
                        'jurisdiction' => $jurisdiction,
                        'title' => "€0 - €0",
                        'score' => $setting['score'],
                        'type' => "",
                        'category' => $setting['slug'],
                        'section' => $section,
                        "data" => ""
                    ];
                }
            }
        }
        DB::loopNodes(function ($connection) use ($insert) {
            $connection->table($this->table)
                ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section', 'data'], ['score']);
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', 'ngr_last_6_months')
                ->orWhere('name', 'ngr_last_6_months')
                ->delete();
        }, true);
    }
}