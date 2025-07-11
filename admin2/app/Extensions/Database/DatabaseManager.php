<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 9/23/16
 * Time: 2:34 PM
 */

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\ConnectionFactory;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;

class DatabaseManager extends BaseDatabaseManager
{
    /**
     * The active connection node link instances.
     *
     * @var array
     */
    protected $nodes = [];

    //TODO in this class I should load all the links a a mysqlasync instance so I can reuse connections as laravel is doing
    /**
     * DatabaseManager constructor.
     * @param mixed $app
     * @param ConnectionFactory $factory
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);
    }

    /**
     * Returns the connection id for the node. $key % number of nodes. Returns null to be able to select the default connection.
     *
     * @param $key
     * @param $table
     * @return int
     */
    public function getShardSelection($key, $table)
    {
        if (!$this->getShardingStatus()) {
            return null;
        }
        if ($this->isSharded($table)) {
            return $this->getEnabledShardSelection($key);
        }
        if ($this->isGlobal($table)) {
            return $this->getGlobalSelection();
        }
        return null;
    }

    public function getGlobalSelection($prefix = 'node'): ?string
    {
        $selection = mt_rand(0, $this->getShardsCount() - 1);

        if (!$selection || isOnDisabledNode($selection)) {
            return null;
        }

        return $prefix . $selection;
    }

    /**
     * Retrieve only enabled shard selection
     *
     * @param $key
     * @param $prefix
     * @return string
     */
    public function getEnabledShardSelection($key, $prefix = 'node'): string
    {
        $node = ((int)$key) % $this->getShardsCount();

        while (isOnDisabledNode($node)) {
            $node = mt_rand(0, $this->getShardsCount() - 1);
        }

        return $prefix . $node;
    }

    /**
     * Returns the connection id for the replica node. $key % number of replica nodes. Returns null to be able to select the default connection.
     * @param $key
     * @param $table
     * @return string|null
     */
    public function getReplicaShardSelection($key, $table)
    {
        if (!$this->getShardingStatus()) {
            return null;
        }
        if ($this->isSharded($table)) {
            return $this->getEnabledShardSelection($key, 'replica_node');
        }
        if ($this->isGlobal($table)) {
            return $this->getGlobalSelection('replica_node');
        }
        return null;
    }

    public function getArchiveShardSelection($key, $table)
    {
        if (!$this->getShardingStatus()) {
            return null;
        }
        if ($this->isSharded($table)) {
            return $this->getEnabledShardSelection($key, 'archive_node');
        }
        if ($this->isGlobal($table)) {
            return $this->getGlobalSelection('archive_node');
        }
        return null;
    }

    /**
     * Returns the number of shards configured
     *
     * @return int
     */
    public function getShardsCount()
    {
        return intval($this->app['config']['database.sharding']['count']);
    }

    /**
     * Returns sharding status.
     *
     * @return bool
     */
    public function getShardingStatus()
    {
        return $this->app['config']['database.sharding']['status'];
    }

    /**
     * Returns list of connections configured.
     *
     * @return array Set of connections.
     */
    public function getConnectionsList()
    {
        return array_keys($this->app['config']['database.connections']);
    }

    public function getNodes()
    {
        return $this->app['config']['database.sharding']['nodes'];
    }

    public function getReplicaNodes()
    {
        return $this->app['config']['database.sharding']['replica_nodes'];
    }

    public function getArchiveNodes()
    {
        return $this->app['config']['database.sharding']['archive_nodes'];
    }

    /**
     * Returns if the table is a sharded table according to the configuration or not.
     * TODO maybe this fits better at the connection class as the array is there anyway or DECIDE if I remove this one
     * @param string $table
     * @return bool
     */
    public function isSharded($table)
    {
        return in_array($table, $this->app['config']['database.sharding']['sharded.tables']) === true;
    }

    public function doSyncShardToMaster()
    {
        return $this->app['config']['database.sharding']['sync.shard.to.master'];
    }

    /**
     * Returns if the table is a global table or not according to the configuration.
     *
     * @param string $table
     * @return bool
     */
    public function isGlobal($table)
    {
        return in_array($table, $this->app['config']['database.sharding']['global.tables']) === true;
    }

    public function isMasterAndSharded($table)
    {
        return in_array($table, $this->app['config']['database.sharding']['master.sharded.tables']) === true;
    }

    public function isMasterConnection($name)
    {
        return empty($name) || in_array($name, $this->app['config']['database.sharding']['masters']) === true;
    }

    public function getShards()
    {
        return $this->app['config']['sharding']['shards'];
    }

    public function getNodesList()
    {
        return $this->app['config']['database.sharding']['nodes.list'];
    }

    /**
     * Get list of replica nodes
     * @return mixed
     */
    public function getReplicaNodesList()
    {
        return $this->app['config']['database.sharding']['replica_nodes.list'];
    }

    /**
     * Get list of archive nodes
     * @return mixed
     */
    public function getArchiveNodesList()
    {
        return $this->app['config']['database.sharding']['archive_nodes.list'];
    }
}
