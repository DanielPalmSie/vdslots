<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class UpdateRG70Description extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $new_description  = "Players who are found VULNERABLE in external registry - " .
            "INDIVIDUAL_INSOLVENCY_REGISTER_MATCH";
        Trigger::where('name', 'RG70')->update(['description' => $new_description]);
    }

    public function down()
    {
        $original_description  = "Players who are found VULNERABLE in external registry";
        Trigger::where('name', 'RG70')->update(['description' => $original_description]);
    }
}
