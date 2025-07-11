<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\RiskProfileRating;
use ParseCsv\Csv as ParseCsv;

class UpdateRiskProfileRatingNationalities extends Seeder
{
    private string $table;
    private string $section;
    private array $jurisdictions;
    private array $new_configs_insertable;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->section = 'AML';
        $this->jurisdictions = array_values(phive('Licensed')->getSetting('country_by_jurisdiction_map'));
        $this->new_configs_insertable = $this->formatConfigsToArray();
    }

    public function up()
    {
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

        $nationalities_csv_path = __DIR__ . "/../data/AMLRiskProfileRatingNationalitiesInserts2.csv";
        $nationalities_csv_path = new ParseCsv($nationalities_csv_path);
        $nationalities_data = $nationalities_csv_path->data;

        foreach ($this->jurisdictions as $jurisdiction) {
            foreach ($nationalities_data as $row) {
                $config[] = [
                    'name' => $row['name'],
                    'jurisdiction' => $jurisdiction,
                    'title' => $row['title'],
                    'category' => 'nationalities',
                    'score' => (int)$row['score'],
                    'section' => $this->section,
                ];
            }
        }

        return $config;
    }
}