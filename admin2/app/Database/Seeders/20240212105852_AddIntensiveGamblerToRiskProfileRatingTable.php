<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddIntensiveGamblerToRiskProfileRatingTable extends Seeder
{
    public function init()
    {
        $this->table = 'risk_profile_rating';
    }

    public function up()
    {
        $insert = [];
        $jurisdiction = 'DGOJ';
        $sections = [
            [
                'title' => 'Intensive Gambler',
                'slug' => 'intensive_gambler',
                'settings' => [
                    ['title' => '0-24 years', 'name' => '0,24', 'score' => 0],
                    ['title' => '25+ years', 'name' => '25', 'score' => 0],
                ]
            ],
        ];
        foreach ($sections as $section) {
            // parent category
            $insert[] = [
                'name' => $section['slug'],
                'jurisdiction' => $jurisdiction,
                'title' => $section['title'],
                'score' => 0,
                'type' => 'option',
                'category' => '',
                'section' => 'RG',
                'data' => '',
            ];
            foreach ($section['settings'] as $setting) {
                // category score settings
                $insert[] = [
                    'title' => $setting['title'],
                    'name' => $setting['name'],
                    'jurisdiction' => $jurisdiction,
                    'score' => $setting['score'],
                    'type' => '',
                    'category' => $section['slug'],
                    'section' => 'RG',
                    'data' => '',
                ];
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
                ->whereIn('category', ['intensive_gambler'])
                ->orWhereIn('name', ['intensive_gambler'])
                ->delete();
        }, true);
    }
}