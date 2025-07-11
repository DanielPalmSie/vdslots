<?php
use App\Extensions\Database\Seeder\Seeder;
use ParseCsv\Csv as ParseCsv;
use App\Models\RiskProfileRating;

class UpdateRiskProfileRatingCountries2 extends Seeder
{
    public function up()
    {
        $countries_csv_path = __DIR__ . "/../data/AMLRiskProfileRatingCountriesUpdate2.csv";

        $countries_csv = new ParseCsv($countries_csv_path);
        $countries_data = $countries_csv->data;

        foreach ($countries_data as $update) {
            $score = $update['score'];
            RiskProfileRating::where('category', 'countries')
                ->where('section', 'AML')
                ->where('name', $update['name'])
                ->update(['score' => $score]);
        }
    }
}
