<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class FixTypoInMethodNameForDepositsAndWithdrawals extends Seeder
{
    public function up()
    {
        $updates = [
            ['ids' => [9312373, 9607003, 9831642, 9642183], 'method' => 'worldpay'],
            ['ids' => [9393071], 'method' => 'payanybank']
        ];

        foreach ($updates as $update) {
            DB::table('pending_withdrawals')
                ->whereIn('id', $update['ids'])
                ->update(['payment_method' => $update['method']]);
        }
    }

    public function down()
    {
        $reverts = [
            ['id' => 9312373, 'method' => ' worldpay'],
            ['id' => 9607003, 'method' => 'worldpay '],
            ['id' => 9642183, 'method' => 'worldpay '],
            ['id' => 9831642, 'method' => 'wsorldpay'],
            ['id' => 9393071, 'method' => 'Payanybank']
        ];

        foreach ($reverts as $revert) {
            DB::table('pending_withdrawals')
                ->where('id', $revert['id'])
                ->update(['payment_method' => $revert['method']]);
        }
    }
}
