<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddNewAMLCategoryOccupation extends Seeder
{
    private string $table;
    private string $category_slug;
    private $countryJurisdictionMap;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->category_slug = 'occupations';
        $this->countryJurisdictionMap = phive('Licensed')->getSetting('country_by_jurisdiction_map');
    }

    public function up()
    {
        $insert = [];
        $occupations_groups = [
            ['name' => 'High Risk Occupations', 'slug' => 'high-risk-occupation-list', 'score' => 80],
        ];

        foreach ($this->countryJurisdictionMap as $jurisdiction) {
            // parent category
            $insert[] = [
                'name' => $this->category_slug,
                'jurisdiction' => $jurisdiction,
                'title' => 'Occupations',
                'score' => 0,
                'type' => 'option',
                'category' => '',
                'section' => 'AML',
                'data' => '',
            ];
            foreach ($occupations_groups as $group) {
                // category score settings
                $insert[] = [
                    'title' => $group['name'],
                    'name' => $group['slug'],
                    'jurisdiction' => $jurisdiction,
                    'score' => $group['score'],
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