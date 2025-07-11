<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\RiskProfileRating;
use App\Extensions\Database\FManager as DB;
use ParseCsv\Csv as ParseCsv;

class SetNationalitiesRiskProfileRating extends Seeder
{
    public function up()
    {
        // Query used to obtain current config:
        //    SELECT name, jurisdiction, title, type, score, 'nationalities' AS category, section, data
        //    FROM risk_profile_rating
        //    WHERE category = 'countries';
        $csv_path = __DIR__ . "/../data/RiskProfileRatingNationalities.csv";
        $this->main($csv_path);
    }

    public function down()
    {
        $csv_path = __DIR__ . "/../data/RiskProfileRatingNationalitiesBackup.csv";
        $this->main($csv_path);
    }

    public function main($csv_path)
    {
        RiskProfileRating::where('category', 'nationalities')
            ->where('section', 'AML')
            ->delete();

        $csv = new ParseCsv($csv_path);
        $nationality_config = $csv->data;

        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $bulkInsertInMasterAndShards('risk_profile_rating', $nationality_config);
    }
}
