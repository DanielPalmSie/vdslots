<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Trigger;

class UpdateRG79Description extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $new_description  = "Customer is top X highest winning customers in the last Y months";
        Trigger::where('name', 'RG79')->update(['description' => $new_description]);
    }

    public function down()
    {
        $original_description  = "Customer is top X highest winning customers that have registered in the last Y months";
        Trigger::where('name', 'RG79')->update(['description' => $original_description]);
    }
}
