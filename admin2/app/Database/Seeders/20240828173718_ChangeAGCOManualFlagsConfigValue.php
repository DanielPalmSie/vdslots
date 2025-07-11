<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class ChangeAGCOManualFlagsConfigValue extends Seeder
{
    /**
     * @var array|mixed
     */
    private $value;
    private bool $isShardedDB;
    private string $table = 'config';

    public function init()
    {
        $this->value = Config::getValue(
            "AGCO-manual-flags",
            "jurisdictions",
            [],
            false,
            true,
            true
        );
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    /**
     * @throws Exception
     */
    public function up()
    {
        if (empty($this->value)) {
            throw new \RuntimeException(
                "The AGCO-manual-flags config value is empty! Check seeder 20240827140433"
            );
        }
        $this->value[] = "AML61";
        $flags = implode(',', $this->value);
        $query = "UPDATE {$this->table} SET config_value = '{$flags}' WHERE config_name = 'AGCO-manual-flags';";
        phive('SQL')->onlyMaster()->query($query);

        if ($this->isShardedDB) {
            phive('SQL')->shs()->query($query);
        }
    }

    public function down()
    {
        $key = array_search("AML61", $this->value);

        if ($key === false) {
            return;
        }

        unset($this->value[$key]);
        $flags = implode(',', $this->value);
        $query = "UPDATE {$this->table} SET config_value = '{$flags}' WHERE config_name = 'AGCO-manual-flags';";
        phive('SQL')->onlyMaster()->query($query);

        if ($this->isShardedDB) {
            phive('SQL')->shs()->query($query);
        }
    }
}