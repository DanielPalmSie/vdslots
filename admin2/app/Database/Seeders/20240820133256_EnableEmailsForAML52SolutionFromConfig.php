<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class EnableEmailsForAML52SolutionFromConfig extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $configName;
    private string $configTag;

    public function init()
    {
        $this->table = 'config';

        $this->configName = 'aml52-payout-details-request-email';
        $this->configTag = 'feature-flag';

        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->update([
                'config_value' => 'on'
            ]);
    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->update([
                'config_value' => 'off'
            ]);
    }
}
