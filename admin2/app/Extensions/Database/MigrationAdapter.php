<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/24/17
 * Time: 5:31 PM
 */

namespace App\Extensions\Database;

use Phpmig\Adapter\AdapterInterface;
use Phpmig\Migration\Migration;

class MigrationAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $adapter;

    public function __construct($adapter, $tableName, $connectionName = '')
    {
        $this->adapter = $adapter->connection($connectionName);
        $this->tableName = $tableName;
    }

    /**
     * Get all migrated version numbers
     *
     * @return array
     */
    public function fetchAll()
    {
        $all = $this->adapter
            ->table($this->tableName)
            ->orderBy('version')
            ->get();

        if (!is_array($all)) {
            $all = $all->toArray();
        }

        return array_map(function ($v) {
            return $v->version;
        }, $all);
    }

    /**
     * Up
     *
     * @param Migration $migration
     * @return AdapterInterface
     */
    public function up(Migration $migration)
    {
        $this->adapter
            ->table($this->tableName)
            ->insert(array(
                'version' => $migration->getVersion()
            ));

        return $this;
    }

    /**
     * Down
     *
     * @param Migration $migration
     * @return AdapterInterface
     */
    public function down(Migration $migration)
    {
        $this->adapter
            ->table($this->tableName)
            ->where('version', $migration->getVersion())
            ->delete();

        return $this;
    }

    /**
     * Is the schema ready?
     *
     * @return bool
     */
    public function hasSchema()
    {
        return $this->adapter->getSchemaBuilder()->hasTable($this->tableName);
    }

    /**
     * Create Schema
     *
     * @return AdapterInterface
     */
    public function createSchema()
    {
        /* @var \Illuminate\Database\Schema\Blueprint $table */
        $this->adapter->getSchemaBuilder()->create($this->tableName, function ($table) {
            $table->string('version');
        });
    }
}