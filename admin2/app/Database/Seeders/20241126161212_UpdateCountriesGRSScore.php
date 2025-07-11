<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateCountriesGRSScore extends Seeder
{
    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->table("risk_profile_rating")
                ->where('category', 'countries')
                ->where('section', 'AML')
                ->whereIn('title', ["ALGERIA", "ANGOLA", "CROATIA", "COTE D'IVOIRE"])
                ->update(['score' => 80]);
        }, true);
    }
}