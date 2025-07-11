<?php

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Connection\ConnectionFactory;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as BaseManager;

class ArchiveManager extends BaseManager
{

    /** @var  MysqlAsync */
    protected $async; //todo move this stuff to the db manager as it makes more sense to have it there

    /**
     * Create a new database capsule manager.
     *
     * @param  Container|null $container
     * @param  array $nodes
     * @param  array $shard_config
     */
    public function __construct(Container $container = null, $nodes = [], $shard_config = [])
    {
        parent::__construct($container);

        $this->setupDefaultNodes($nodes);

        $this->setupDefaultShardConfig($shard_config);
    }

    protected function setupDefaultNodes($nodes = [])
    {
        $this->container['config']['database.archive_nodes'] = $nodes;
    }

    protected function setupDefaultShardConfig($config = [])
    {
        $this->container['config']['database.tables.global'] = $config['global'];
        $this->container['config']['database.tables.sharded'] = $config['sharded'];
        $this->container['config']['database.tables.master.sharded'] = $config['master.sharded'];
    }

    /**
     * Sets sharding configuration
     *
     * @param array $config
     */
    public function setShardingConfig($config)
    {
        $this->container['config']['database.archive_sharding'] = $config;
    }

    /**
     * Build the database manager instance.
     *
     * @return void
     */
    protected function setupManager()
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }


    public static function isSharded($table)
    {
        $raw_table = preg_match('/\s/', $table) ? explode(' ', $table)[0] : $table;

        return static::$instance->getDatabaseManager()->isSharded($raw_table);
    }

    public static function doSyncShardToMaster()
    {
        return static::$instance->getDatabaseManager()->doSyncShardToMaster();
    }

    public static function isGlobal($table)
    {
        $raw_table = preg_match('/\s/', $table) ? explode(' ', $table)[0] : $table;

        return static::$instance->getDatabaseManager()->isGlobal($raw_table);
    }

    public static function isMasterAndSharded($table)
    {
        $raw_table = preg_match('/\s/', $table) ? explode(' ', $table)[0] : $table;

        return static::$instance->getDatabaseManager()->isMasterAndSharded($raw_table);
    }

    public static function getNodesList()
    {
        return static::$instance->getDatabaseManager()->getArchiveNodesList();
    }

    public static function getConnectionsList()
    {
        return static::$instance->getDatabaseManager()->getConnections();
    }

    public static function getNodes()
    {
        return static::$instance->getDatabaseManager()->getArchiveNodes();
    }

    /**
     * @return Connection
     */
    public static function getMasterConnection()
    {
        return static::$instance->getConnection('videoslots_archived');
    }

    /**
     * Get a connection instance based on the key and table. Useful when for instance trying to do sharded transactions
     * @param $table
     * @param $shard_key
     * @return Connection
     */
    public static function connectionByKey($table, $shard_key)
    {
        return static::$instance->getConnection(static::$instance->getDatabaseManager()->getArchiveShardSelection($shard_key, $table));
    }

    public static function connectionFromModel(FModel $model)
    {
        return self::connectionByKey($model->getTable(), $model->getShardKeyId());
    }

    /**
     * Loop the nodes and apply the closure given, if do master is true then it will include also the master connection
     *
     * @param \Closure $closure
     * @param bool $do_master
     * @return bool
     */
    public static function loopNodes(\Closure $closure, $do_master = false)
    {
        /** @var Connection[] $connections */
        $connections = self::getConnectionsList();
        $res = [];

        foreach (self::getNodesList() as $node_name) {
            if (isOnDisabledNode($node_name)) {
                continue;
            }
            $res[] = $closure($connections[$node_name]);
        }

        if ($do_master === true) {
            $res[] = $closure($connections['videoslots_archived']);
        }

        return count($res) == count(array_filter($res));
    }
}
