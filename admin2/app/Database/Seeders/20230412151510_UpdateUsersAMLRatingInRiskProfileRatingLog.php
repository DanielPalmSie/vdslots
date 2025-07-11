<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\RiskProfileRatingLog;

/**
 * Migrate user's AML Global Rating from range 0 - 10 to 0 - 100
 */
class UpdateUsersAMLRatingInRiskProfileRatingLog extends Seeder
{

    public function up()
    {
        RiskProfileRatingLog::where('rating_type', 'AML')
            ->where('rating', '>', 0)
            ->where('rating', '<=', 10)
            ->update(['rating' => DB::raw('rating * 10')]);
    }

    public function down()
    {
        RiskProfileRatingLog::where('rating_type', 'AML')
            ->where('rating', '>=', 10)
            ->where('rating', '<=', 100)
            ->update(['rating' => DB::raw('ROUND(rating / 10, 0)')]);
    }
}