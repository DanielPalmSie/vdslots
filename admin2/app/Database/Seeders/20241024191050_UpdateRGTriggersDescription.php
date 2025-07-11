<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Trigger;

class UpdateRGTriggersDescription extends Seeder
{
    public function up()
    {

        $data = [
            'RG19' => [
                'description' => 'Net deposits are over a threshold taking in consideration the amount declared in the source of funds declaration.',
            ],
            'RG20' => [
                'indicator_name' => 'Y EUR Net deposit Last X Days.',
                'description' => 'Accounts that their net deposit in the last X days is more than Y EUR.',
            ],
            'RG38' => [
                'indicator_name' => 'Net deposit > 10,000',
                'description' => 'Customer has lost more than €10,000 Net deposit in the last 30 days. Trigger maximum once every 30 days.',
            ],
            'RG39' => [
                'indicator_name' => 'Net deposit > 20,000',
                'description' => 'Customer has lost more than €20,000 Net deposit in the last 30 days. Trigger maximum once every 30 days.',
            ],
        ];

        foreach ($data as $trigger => $to_update) {
            Trigger::where('name', $trigger)->update($to_update);
        }
    }

    public function down()
    {
        $data = [
            'RG19' => [
                'description' => 'Losses are over a threshold taking in consideration the amount declared in the source of funds declaration',
            ],
            'RG20' => [
                'indicator_name' => 'Y EUR NGR last X days',
                'description' => 'Accounts that their NGR in the last X days is more than Y EUR',
            ],
            'RG38' => [
                'indicator_name' => 'Lost > NGR 10.000',
                'description' => 'Should trigger if a customer have lost €10,000 NGR last 30 days, trigger maximum one time every 30 days.',
            ],
            'RG39' => [
                'indicator_name' => 'Lost > NGR 20.000',
                'description' => 'Should trigger if a customer have lost €20,000 NGR last 30 days, trigger maximum one time every 30 days.',
            ],
        ];

        foreach ($data as $trigger => $to_update) {
            Trigger::where('name', $trigger)->update($to_update);
        }
    }
}