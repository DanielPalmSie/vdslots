<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\RiskProfileRating;
use App\Extensions\Database\FManager as DB;
use ParseCsv\Csv as ParseCsv;

class UpdateRiskProfileRatingNationalitiesCountries extends Seeder
{
    private string $table;
    private string $section;
    private array $categories;
    private array $jurisdictions;
    private array $new_configs;
    private array $new_configs_insertable;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->section = 'AML';
        $this->categories = ['countries', 'nationalities'];

        $this->jurisdictions = array_values(phive('Licensed')->getSetting('country_by_jurisdiction_map'));
        $this->new_configs = [
            'UM' => [
                'title' => strtoupper('United States Minor Outlying Islands'),
                'score' => 40,
            ],
            'SS' => [
                'title' => strtoupper('South Sudan'),
                'score' => 90,
            ],
            'BL' => [
                'title' => strtoupper('Saint-Barthélemy'),
                'score' => 40,
            ],
        ];
        $this->new_configs_insertable = $this->formatConfigsToArray();
    }

    public function up()
    {
        $countries_csv_path = __DIR__ . "/../data/AMLRiskProfileRatingCountriesUpdate.csv";

        $countries_csv = new ParseCsv($countries_csv_path);
        $countries_data = $countries_csv->data;

        foreach ($countries_data as $update) {
            $score = $update['score'];
            RiskProfileRating::where('category', 'countries')
                ->where('section', $this->section)
                ->where('name', $update['name'])
                ->update(['score' => $score]);
        }

        RiskProfileRating::where('category', 'nationalities')
            ->where('section', $this->section)
            ->delete();

        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $bulkInsertInMasterAndShards($this->table, $this->new_configs_insertable);
    }

    public function formatConfigsToArray(): array
    {
        $config = [];

        foreach ($this->jurisdictions as $jurisdiction) {
            foreach ($this->new_configs as $name => $values) {
                $config[] = [
                    'name' => $name,
                    'jurisdiction' => $jurisdiction,
                    'title' => $values['title'],
                    'category' => 'countries',
                    'score' => $values['score'],
                    'section' => $this->section,
                ];
            }
        }

        $nationalities_csv_path = __DIR__ . "/../data/AMLRiskProfileRatingNationalitiesInserts.csv";
        $nationalities_csv_path = new ParseCsv($nationalities_csv_path);
        $nationalities_data = $nationalities_csv_path->data;

        foreach ($this->jurisdictions as $jurisdiction) {
            foreach ($nationalities_data as $row) {
                $config[] = [
                    'name' => $row['name'],
                    'jurisdiction' => $jurisdiction,
                    'title' => $row['title'],
                    'category' => 'nationalities',
                    'score' => $row['score'],
                    'section' => $this->section,
                ];
            }
        }

        return $config ?? [];
    }
}
