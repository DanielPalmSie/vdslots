<?php

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Connection\ConnectionFactory;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as BaseManager;

class ReplicaManager extends BaseManager
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
        $this->container['config']['database.replica_nodes'] = $nodes;
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
        $this->container['config']['database.replica_sharding'] = $config;
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
        return static::$instance->getDatabaseManager()->getReplicaNodesList();
    }

    public static function getConnectionsList()
    {
        return static::$instance->getDatabaseManager()->getConnections();
    }

    public static function getNodes()
    {
        return static::$instance->getDatabaseManager()->getReplicaNodes();
    }

    /**
     * @return Connection
     */
    public static function getMasterConnection()
    {
        return static::$instance->getConnection('replica');
    }

    /**
     * Get a connection instance based on the key and table. Useful when for instance trying to do sharded transactions
     * @param $table
     * @param $shard_key
     * @return Connection
     */
    public static function connectionByKey($table, $shard_key)
    {
        return static::$instance->getConnection(static::$instance->getDatabaseManager()->getReplicaShardSelection($shard_key, $table));
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
            $res[] = $closure($connections['replica']);
        }

        return count($res) == count(array_filter($res));
    }

    /**
     * @return mixed
     */
    public static function getAllConnectionsQueryLog()
    {
        $connections_list = static::$instance->getDatabaseManager()->getConnectionsList();
        $profile_result['totals']['count'] = 0;
        $profile_result['totals']['time'] = (float)0;
        foreach ($connections_list as $connection) {
            $query_log = self::connection($connection)->getQueryLog();
            $profile_result['totals']['count'] += count($query_log);

            foreach ($query_log as $query) {
                $profile_result['totals']['time'] += $query['time'];
                if (isset($query['all']) && $query['all'] === true) {
                    $profile_result['query_list'][$query['query']]['connection'] = "cross-shard | $connection";
                } else {
                    $profile_result['query_list'][$query['query']]['connection'] = $connection;
                }
                $profile_result['query_list'][$query['query']]['name'] = $query['query'];
                if (count($query['bindings']) > 0) {
                    $profile_result['query_list'][$query['query']]['name'] .= ' #Bindings: ' . json_encode($query['bindings']);
                }
                $profile_result['query_list'][$query['query']]['time'] += $query['time'];
                $profile_result['query_list'][$query['query']]['count'] += 1;
            }
        }
        $profile_result['query_list'] = collect($profile_result['query_list'])->sortByDesc('time')->values()->all();
        return $profile_result;
    }
}
