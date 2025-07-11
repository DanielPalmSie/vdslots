<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\RiskProfileRating;

/**
 * Migrate AML Rating score settings from range 0 - 10 to 0 - 100
 */
class UpdateRiskProfileRatingSettings extends Seeder
{
    /**
     * @var array|string[]
     */
    private array $categories;

    public function init()
    {
        $this->categories = [
            'age',
            'countries',
            'deposit_method',
            'deposit_vs_wager',
            'deposited_last_12_months',
            'wagered_last_12_months',
            'game_type',
            'ngr_last_12_months',
            'pep',
            'sanction_list',
        ];
    }

    public function up()
    {
        RiskProfileRating::whereIn('category', $this->categories)
            ->where('section', 'AML')
            ->where('score', '>', 0)
            ->where('score', '<=', 10)
            ->update(['score' => DB::raw('score * 10')]);
    }

    public function down()
    {
        RiskProfileRating::whereIn('category', $this->categories)
            ->where('section', 'AML')
            ->where('score', '>=', 10)
            ->where('score', '<=', 100)
            ->update(['score' => DB::raw('ROUND(score / 10, 0)')]);
    }
}