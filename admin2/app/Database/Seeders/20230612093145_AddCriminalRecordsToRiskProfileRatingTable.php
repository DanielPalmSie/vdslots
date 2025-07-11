<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddCriminalRecordsToRiskProfileRatingTable extends Seeder
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
            [
                'title' => 'Criminal Record',
                'slug' => 'criminal_records',
                'settings' => [
                    ['name' => 'No Criminal Record', 'score' => 0, 'data' => ['slug' => 'no_criminal_record']],
                    ['name' => 'Minor offence', 'score' => 60, 'data' => ['slug' => 'minor_offence']],
                    ['name' => 'Serious offence', 'score' => 100, 'data' => ['slug' => 'serious_offence']],
                ]
            ],
        ];
        foreach ($country_jurisdiction_map as $jurisdiction) {
            foreach ($sections as $section) {
                // parent category
                $insert[] = [
                    'name' => $section['slug'],
                    'jurisdiction' => $jurisdiction,
                    'title' => $section['title'],
                    'score' => 0,
                    'type' => 'option',
                    'category' => '',
                    'section' => 'AML',
                    'data' => '',
                ];
                foreach ($section['settings'] as $setting) {
                    // category score settings
                    $insert[] = [
                        'name' => $setting['name'],
                        'jurisdiction' => $jurisdiction,
                        'title' => $setting['name'],
                        'score' => $setting['score'],
                        'type' => '',
                        'category' => $section['slug'],
                        'section' => 'AML',
                        'data' => json_encode($setting['data']),
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
                ->whereIn('category', ['criminal_records'])
                ->orWhereIn('name', ['criminal_records'])
                ->delete();
        }, true);
    }
}