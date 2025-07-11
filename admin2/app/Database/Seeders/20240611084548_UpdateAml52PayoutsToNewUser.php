<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateAml52PayoutsToNewUser extends Seeder
{
    private string $table = 'users';
    private Connection $connection;
    private const BATCH_SIZE = 100;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $systemUser = $this->connection
            ->table($this->table)
            ->where('username', 'system')
            ->first();

        $systemAml52User = $this->connection
            ->table($this->table)
            ->where('username', 'system_aml52_payout')
            ->first();

        if (!$systemUser || !$systemAml52User) {
            throw new LogicException('System users must exist. Run seeders');
        }

        $payouts = DB::table('pending_withdrawals')
            ->select('id')
            ->where('created_by', $systemUser->id)
            ->get();

        $count = 0;
        $ids = [];

        foreach ($payouts as $payout) {
            $ids[] = $payout->id;
            $count++;

            if ($count % self::BATCH_SIZE === 0) {
                DB::table('pending_withdrawals')
                    ->whereIn('id', $ids)
                    ->update(['created_by' => $systemAml52User->id]);

                $ids = [];
            }
        }

        DB::table('pending_withdrawals')
            ->whereIn('id', $ids)
            ->update(['created_by' => $systemAml52User->id]);
    }

    public function down()
    {
        $systemUser = $this->connection
            ->table($this->table)
            ->where('username', 'system')
            ->first();

        $systemAml52User = $this->connection
            ->table($this->table)
            ->where('username', 'system_aml52_payout')
            ->first();

        $payouts = DB::table('pending_withdrawals')
            ->select('id')
            ->where('created_by', $systemAml52User->id)
            ->get();

        $count = 0;
        $ids = [];

        foreach ($payouts as $payout) {
            $ids[] = $payout->id;
            $count++;

            if ($count % self::BATCH_SIZE === 0) {
                DB::table('pending_withdrawals')
                    ->whereIn('id', $ids)
                    ->update(['created_by' => $systemUser->id]);

                $ids = [];
            }
        }

        DB::table('pending_withdrawals')
            ->whereIn('id', $ids)
            ->update(['created_by' => $systemUser->id]);
    }
}
