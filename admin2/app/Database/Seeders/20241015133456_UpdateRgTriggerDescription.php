<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Trigger;

class UpdateRgTriggerDescription extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        Trigger::where('name', 'RG66')->update(['description' => 'Customer has Net Deposit of €X within the last Y days']);
        Trigger::where('name', 'RG68')->update(['indicator_name' => 'X% of Net Deposit Threshold reached']);
        Trigger::where('name', 'RG71')->update([
            'indicator_name' => 'Financial Check',
            'description' => 'Players in external registry - JUDGMENTS_ORDERS_FINES_REGISTER_MATCH',
        ]);
    }

    public function down()
    {
        Trigger::where('name', 'RG66')->update(['description' => 'Customer has Net Deposit of €1500 within the last 30 days']);
        Trigger::where('name', 'RG68')->update(['indicator_name' => '50% of Net Deposit Threshold reached']);
        Trigger::where('name', 'RG71')->update([
            'indicator_name' => 'Financial Vulnerability Check',
            'description' => 'Players who are found VULNERABLE in external registry - JUDGMENTS_ORDERS_FINES_REGISTER_MATCH',
        ]);
    }
}
