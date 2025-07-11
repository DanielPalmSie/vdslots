<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AlterRiskProfileRatingTitle extends Seeder
{
    protected string $table;
    protected Connection $connection;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('name', 'canceled_withdrawals_last_x_days')
            ->update(
                [
                    'title' => 'Cancelled withdrawals last _DAYS days',
                    'name' => 'cancelled_withdrawals_last_x_days',
                ]
            );
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('name', 'cancelled_withdrawals_last_x_days')
            ->update(
                [
                    'title' => 'Canceled withdrawals last _DAYS days',
                    'name' => 'canceled_withdrawals_last_x_days',
                ]
            );
    }
}
