<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddFirstDepositBonusIdForGB extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private string $config_name;
    private string $config_tag;
    private int $config_value;
    private string $config_type;
    protected $schema;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->config_name = 'first-deposit-bonus-id';
        $this->config_tag = 'license-gb';
        $this->config_value = 39011;
        $this->config_type = '{"type":"number"}';
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== 'megariches') {
            return;
        }

        // Handle the master change
        $this->insertConfig($this->connection, $this->table);

        // Handle the shards change
        if ($this->schema->hasTable($this->table)) {
            DB::loopNodes(function (Connection $shardConnection) {
                $this->insertConfig($shardConnection, $this->table);
            }, false);
        }
    }

    public function down()
    {
        $this->init();

        if ($this->brand !== 'megariches') {
            return;
        }

        // Rollback the master change
        $this->rollbackConfig($this->connection, $this->table);

        // Rollback the shards change
        if ($this->schema->hasTable($this->table)) {
            DB::loopNodes(function (Connection $shardConnection) {
                $this->rollbackConfig($shardConnection, $this->table);
            }, false);
        }
    }

    private function insertConfig(Connection $connection, string $table)
    {
        $configData = $connection
            ->table($table)
            ->where('config_name', $this->config_name)
            ->where('config_tag', $this->config_tag)
            ->first();

        if (empty($configData)) {
            $connection
                ->table($table)
                ->insert([
                    'config_name'=> $this->config_name,
                    'config_tag'=> $this->config_tag,
                    'config_value' => $this->config_value,
                    'config_type' => $this->config_type,
                ]);
        } else {
            $connection
                ->table($table)
                ->where('config_name', $this->config_name)
                ->where('config_tag', $this->config_tag)
                ->update(['config_value' => $this->config_value]);
        }
    }

    private function rollbackConfig(Connection $connection, string $table)
    {
        $configData = $connection
            ->table($table)
            ->where('config_name', $this->config_name)
            ->where('config_tag', $this->config_tag)
            ->where('config_value', $this->config_value)
            ->first();

        if (!empty($configData)) {
            $connection
                ->table($table)
                ->where('config_name', $this->config_name)
                ->where('config_tag', $this->config_tag)
                ->where('config_value', $this->config_value)
                ->delete();
        }
    }

}