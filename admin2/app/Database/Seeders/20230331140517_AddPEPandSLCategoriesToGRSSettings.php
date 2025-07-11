<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddPEPandSLCategoriesToGRSSettings extends Seeder
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
            'AML' => [
                ['title' => 'PEP', 'slug' => 'pep', 'score' => 10],
                ['title' => 'Sanction List', 'slug' => 'sanction_list', 'score' => 10],
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
                        'category' => '',
                        'section' => $section,
                    ];
                    // category score settings
                    $insert[] = [
                        'name' => '',
                        'jurisdiction' => $jurisdiction,
                        'title' => '',
                        'score' => $setting['score'],
                        'type' => '',
                        'category' => $setting['slug'],
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
                ->whereIn('category', ['pep', 'sanction_list'])
                ->orWhereIn('name', ['pep', 'sanction_list'])
                ->delete();
        }, true);
    }

}