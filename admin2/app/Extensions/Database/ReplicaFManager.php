<?php

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use App\Extensions\Database\ReplicaManager as MyBaseManager;
use App\Extensions\Database\FManager as FManager;

class ReplicaFManager extends MyBaseManager
{
    /**
     * TODO ideal about this is to reuse the connection
     */
    protected function setupAsync()
    {
        $this->async = new MysqlAsync(static::$instance->getDatabaseManager()->getReplicaNodes());
    }

    /**
     * Get a fluent query builder instance and setup the connection of the shard by a given key.
     * TODO $this->getShardingStatus()
     *
     * @param  string $key
     * @param  string $table
     * @param  $fetchMode
     * @return \Illuminate\Database\Query\Builder
     */
    public static function shTable($key, $table, $fetchMode = null)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::shTable($key, $table, $fetchMode);
        }
        /** @var self $instance */
        $instance = static::$instance;

        if (!is_null($fetchMode)) {
            $instance->setFetchMode($fetchMode);//TODO check if this is still possible lit this
        }
        $replicaConnectionName = $instance->getDatabaseManager()->getReplicaShardSelection($key, preg_split("/[\s]+/", $table)[0]);
        // If $replicaConnectionName returns null then it will connect to default master database. But we need to connect to replica master database.
        $connectionName = $replicaConnectionName == null ?  'replica' : $replicaConnectionName;
        return self::table($table, $connectionName, true);
    }

    /**
     * @inheritdoc
     *
     * @param  string $table
     * @param  string $connection
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($table, $connection = null, $direct = false)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::table($table, $connection, $direct);
        }
        $raw_table = preg_match('/\s/', $table) ? explode(' ', $table)[0] : $table;
        if ($direct !== true && self::isSharded($raw_table)) {
            if (is_null($connection) && self::isMasterAndSharded($raw_table)) {
                throw new \Exception("Ambiguous table state, connection must be specified manually on tables both sharded and global.");
            }
            return static::$instance->connection($connection)->table($table)->shs();
        } else {
            return static::$instance->connection($connection)->table($table);
        }
    }

    /**
     * Run a select statement against the a sharded node.
     *
     * @param  mixed $key
     * @param  string $query
     * @param  string $table
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return array
     */
    public static function shSelect($key, $table, $query, $bindings = [], $useReadPdo = true)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::shSelect($key, $table, $query, $bindings, $useReadPdo);
        }
        return static::$instance->connection(static::$instance->getDatabaseManager()->getReplicaShardSelection($key, $table))
            ->select($query, $bindings, $useReadPdo);
    }

    /**
     * Execute a raw query select from a given
     *
     * @param $table
     * @param $query
     * @param array $bindings
     * @param array $params
     * @param null|\Closure $closure
     * @return array
     */
    public static function shsSelect($table, $query, $bindings = [], $params = [], $closure = null)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::shsSelect($table, $query, $bindings, $params, $closure);
        }
        //todo if it is global do the random thing
        return static::$instance->getConnection('replica')->select($query, $bindings, null, static::$instance->getDatabaseManager()->isSharded($table), $params, $closure, replicaDatabaseSwitcher());
    }

    public static function shsAggregate($table, $query, $bindings = [], $closure = MysqlAsync::RES_AGGREGATE)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::shsAggregate($table, $query, $bindings, $closure);
        }
        return self::shsSelect($table, $query, $bindings, null, $closure);
    }

    public static function shsStatement($table, $query, $bindings = [])
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::shsStatement($table, $query, $bindings);
        }
        return self::statement($query, $bindings, static::$instance->getDatabaseManager()->isSharded($table));
    }

    public static function shBeginTransaction($do_master = false)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::loopNodes(function (Connection $connection) {
                $connection->beginTransaction();
            }, $do_master);
        }
        self::loopNodes(function (Connection $connection) {
            $connection->beginTransaction();
        }, $do_master);
    }

    public static function shCommit($do_master = false)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::loopNodes(function (Connection $connection) {
                $connection->commit();
            }, $do_master);
        }
        self::loopNodes(function (Connection $connection) {
            $connection->commit();
        }, $do_master);
    }

    public static function shRollback($do_master = false)
    {
        if(replicaDatabaseSwitcher() == false) {
            return FManager::loopNodes(function (Connection $connection) {
                $connection->rollBack();
            }, $do_master);
        }
        self::loopNodes(function (Connection $connection) {
            $connection->rollBack();
        }, $do_master);
    }

    /**
     * Convert query builder to raw sql
     * @param Builder $base_query
     * @return string
     */
    public static function getSql($base_query)
    {
        $sql        = $base_query->toSql();
        $bindings   = $base_query->getBindings();
        $needle     = '?';
        foreach ($bindings as $replace)
        {
            $pos = strpos($sql, $needle);
            if ($pos !== false)
            {
                if (gettype($replace) === "string")
                {
                    $replace = ' "'.addslashes($replace).'" ';
                }
                $sql = substr_replace($sql, $replace, $pos, strlen($needle));
            }
        }
        return $sql;
    }

    /**
     * @return mixed
     */
    public static function getShardingStatus() {
        return static::$instance->getDatabaseManager()->getShardingStatus();
    }

}
