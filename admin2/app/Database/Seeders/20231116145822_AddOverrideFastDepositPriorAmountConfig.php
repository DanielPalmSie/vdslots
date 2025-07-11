<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddOverrideFastDepositPriorAmountConfig extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $configName;
    private string $configTag;

    public function init()
    {
        $this->table = 'config';
        $this->configName = 'override-fast-deposit-prior-amounts';
        $this->configTag = 'cashier';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $configData = [
            'config_name' => $this->configName,
            'config_tag' => $this->configTag,
            'config_type' => '{"type":"template", "delimiter":"::", "next_data_delimiter":"\n" , "format":"<:Currency><delimiter><:Amount>"}',
            'config_value' => ''
        ];
        $this->connection->table($this->table)->insert([$configData]);
    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->delete();
    }
}
