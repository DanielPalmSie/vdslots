<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddWageredLastXMonthsSportsbookToAMLGRSSetting extends Seeder
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
                ['title' => 'Wagered last _MONTHS months (Sportsbook)', 'slug' => 'wagered_last_12_months_sportsbook'],
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
                        'data' => json_encode([
                            "replacers" => [
                                "_MONTHS" => 12
                            ]
                        ]),
                    ];
                }
            }
        }
        DB::loopNodes(function ($connection) use ($insert) {
            $connection->table($this->table)
                ->upsert($insert, ['name', 'jurisdiction', 'type', 'category', 'section']);

        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('category', 'wagered_last_12_months_sportsbook')
                ->orWhere('name', 'wagered_last_12_months_sportsbook')
                ->delete();
        }, true);
    }
}
