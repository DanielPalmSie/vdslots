<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\RiskProfileRating;

class SetRiskProfileRatingScoreSettings extends Seeder
{

    /**
     * @var array|array[]
     */
    private array $old_score_range;
    /**
     * @var array|array[]
     */
    private array $new_score_range;

    public function init()
    {
        $this->old_score_range = [
            'RG' => [
                ['tag' => 'Social Gambler', 'score' => 40],
                ['tag' => 'Low Risk', 'score' => 49],
                ['tag' => 'Medium Risk', 'score' => 79],
                ['tag' => 'High Risk', 'score' => 1000],
            ],
            'AML' => [
                ['tag' => 'Social Gambler', 'score' => 49],
                ['tag' => 'Low Risk', 'score' => 69],
                ['tag' => 'Medium Risk', 'score' => 79],
                ['tag' => 'High Risk', 'score' => 100],
            ]
        ];

        $this->new_score_range = [
            'RG' => [
                ['tag' => 'Social Gambler', 'score' => 59],
                ['tag' => 'Low Risk', 'score' => 69],
                ['tag' => 'Medium Risk', 'score' => 79],
                ['tag' => 'High Risk', 'score' => 100],
            ],
            'AML' => [
                ['tag' => 'Social Gambler', 'score' => 49],
                ['tag' => 'Low Risk', 'score' => 69],
                ['tag' => 'Medium Risk', 'score' => 79],
                ['tag' => 'High Risk', 'score' => 100],
            ]
        ];
    }

    public function up()
    {
        foreach ($this->new_score_range as $section => $settings) {
            foreach($settings as $setting){
                RiskProfileRating::where('category', 'rating_score')
                    ->where('section', $section)
                    ->where('name', $setting['tag'])
                    ->update(['score' => $setting['score']]);
            }
        }
    }

    public function down()
    {
        foreach ($this->old_score_range as $section => $settings) {
            foreach($settings as $setting){
                RiskProfileRating::where('category', 'rating_score')
                    ->where('section', $section)
                    ->where('name', $setting['tag'])
                    ->update(['score' => $setting['score']]);
            }
        }
    }
}