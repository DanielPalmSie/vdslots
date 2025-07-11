<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Schema\Blueprint;


class UpdateBoosterConfigValueForKunga extends Seeder
{

    protected string $tableConfig;
    private Connection $connection;

    private array $configData;
    protected string $newConfigValue;
    protected $schema;
    private $brand;
    private $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->tableConfig = 'config';
        $this->newConfigValue = 'no';

        $this->schema = $this->get('schema');

        $this->configData = [
            [
                'config_name' => 'booster',
                'config_tag' => 'auto',
                'config_value' => 'yes'
            ],
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Update config record
        |--------------------------------------------------------------------------
       */

        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        // Handle the master adjustment
        foreach ($this->configData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableConfig)
                ->where('config_name', '=', $data['config_name'])
                ->where('config_tag', '=', $data['config_tag'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableConfig)
                    ->where('config_name', '=', $data['config_name'])
                    ->where('config_tag', '=', $data['config_tag'])
                    ->where('config_value', '=', $data['config_value'])
                    ->update(['config_value' => $this->newConfigValue]);
            }
        }

        // Handle the shards adjustment
        foreach ($this->configData as $data) {
            $this->data = $data;
            if ($this->schema->hasTable($this->tableConfig)) {
                $this->schema->table($this->tableConfig, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (Connection $shardConnection) {
                        $isDataExists = $shardConnection->table($this->tableConfig)
                            ->where('config_name', '=', $this->data['config_name'])
                            ->where('config_tag', '=', $this->data['config_tag'])
                            ->exists();

                        if ($isDataExists) {
                            $shardConnection
                                ->table($this->tableConfig)
                                ->where('config_name', '=', $this->data['config_name'])
                                ->where('config_tag', '=', $this->data['config_tag'])
                                ->where('config_value', '=', $this->data['config_value'])
                                ->update(['config_value' => $this->newConfigValue]);
                        }
                    }, false);
                });
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Update to previous config record
        |--------------------------------------------------------------------------
        */

        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        // Rollback the master adjustment
        foreach ($this->configData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableConfig)
                ->where('config_name', '=', $data['config_name'])
                ->where('config_tag', '=', $data['config_tag'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableConfig)
                    ->where('config_name', '=', $data['config_name'])
                    ->where('config_tag', '=', $data['config_tag'])
                    ->update(['config_value' => $data['config_value']]);
            }
        }

        // Rollback the shards adjustment
        foreach ($this->configData as $data) {
            $this->data = $data;
            if ($this->schema->hasTable($this->tableConfig)) {
                $this->schema->table($this->tableConfig, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (Connection $shardConnection) {
                        $isDataExists = $shardConnection->table($this->tableConfig)
                            ->where('config_name', '=', $this->data['config_name'])
                            ->where('config_tag', '=', $this->data['config_tag'])
                            ->exists();

                        if ($isDataExists) {
                            $shardConnection
                                ->table($this->tableConfig)
                                ->where('config_name', '=', $this->data['config_name'])
                                ->where('config_tag', '=', $this->data['config_tag'])
                                ->update(['config_value' => $this->data['config_value']]);
                        }
                    }, false);
                });
            }
        }
    }
}