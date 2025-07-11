<?php

namespace App\Extensions\Database\Seeder;

use App\Extensions\Database\MigrationAdapter;
use Phpmig\Adapter\AdapterInterface;
use Phpmig\Migration\Migration;

class SeederAdapter extends MigrationAdapter
{

    /**
     * @param Migration $migration
     * @return AdapterInterface
     */
    public function up(Migration $migration)
    {
        $version_exists = $this->adapter
            ->table($this->tableName)
            ->where(['version' => $migration->getVersion()])
            ->exists();

        if(!$version_exists) {
            $this->adapter
                ->table($this->tableName)
                ->insert(array(
                    'version' => $migration->getVersion()
            ));
        }

        return $this;
    }
}