<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\RiskProfileRating;

class ChangeCategoryTypeForPepAndSanctionList extends Seeder
{
    public function up()
    {
        RiskProfileRating::whereIn('name', ['pep', 'sanction_list'])
            ->where('section', 'AML')
            ->where('type', 'interval')
            ->update(['type' => 'option']);

        RiskProfileRating::where('category', 'pep')
            ->where('section', 'AML')
            ->update(['name' => 'PEP', 'title' => 'Politically Exposed Person']);

        RiskProfileRating::where('category', 'sanction_list')
            ->where('section', 'AML')
            ->update(['name' => 'SL', 'title' => 'Sanction List']);
    }

    public function down()
    {
        RiskProfileRating::whereIn('name', ['pep', 'sanction_list'])
            ->where('section', 'AML')
            ->where('type', 'option')
            ->update(['type' => 'interval']);

        RiskProfileRating::whereIn('category', ['pep', 'sanction_list'])
            ->where('section', 'AML')
            ->update(['name' => '', 'title' => '']);
    }
}