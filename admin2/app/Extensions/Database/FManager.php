<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 9/23/16
 * Time: 2:31 PM
 */

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use App\Extensions\Database\Manager as MyBaseManager;

/**
 * @method static array select($query, $bindings = [], $useReadPdo = true, $loopShards = false, $shParams = [], $closure = false) @see \App\Extensions\Database\Connection\Connection::select
 */
class FManager extends MyBaseManager
{
    /**
     * TODO ideal about this is to reuse the connection
     */
    protected function setupAsync()
    {
        $this->async = new MysqlAsync(static::$instance->getDatabaseManager()->getNodes());
    }

    public static function doArchiveDb()
    {
        return self::connection('videoslots_archived');
    }

    /**
     * Get a fluent query builder instance and setup the connection of the shard by a given key.
     * TODO $this->getShardingStatus()
     *
     *
     * @param  string $key
     * @param  string $table
     * @param  $fetchMode
     * @return \Illuminate\Database\Query\Builder
     */
    public static function shTable($key, $table, $fetchMode = null)
    {
        /** @var self $instance */
        $instance = static::$instance;

        if (!is_null($fetchMode)) {
            $instance->setFetchMode($fetchMode);//TODO check if this is still possible lit this
        }

        return $instance->table($table, $instance->getDatabaseManager()->getShardSelection($key, preg_split("/[\s]+/", $table)[0]), true);
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
        return static::$instance->connection(static::$instance->getDatabaseManager()->getShardSelection($key, $table))
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
        //todo if it is global do the random thing
        return self::select($query, $bindings, null, static::$instance->getDatabaseManager()->isSharded($table), $params, $closure);
    }

    public static function shsAggregate($table, $query, $bindings = [], $closure = MysqlAsync::RES_AGGREGATE)
    {
        return self::shsSelect($table, $query, $bindings, null, $closure);
    }

    public static function shsStatement($table, $query, $bindings = [])
    {
        return self::statement($query, $bindings, static::$instance->getDatabaseManager()->isSharded($table));
    }

    public static function bulkInsert($table, $key, $data, Connection $connection = null, $skip_master = false)
    {
        if (empty($data)) {
            return true;
        }

        if (!is_null($connection) && $connection instanceof Connection) {
            $res = [];
            foreach (array_chunk($data, 2000) as $batch) {
                $res[] = $connection->table($table)->insert($batch, null, true);
            }
            return count($res) == count(array_filter($res));
        } elseif (self::isSharded($table)) {
            if (self::isMasterAndSharded($table) && empty($skip_master)) {
                $master_connection = self::getMasterConnection();
                foreach (array_chunk($data, 2000) as $batch) {
                    $master_connection->table($table)->insert($batch, false, true);
                }
            }

            // Split data into shards
            $n_count = count(self::getNodesList());
            $spliced_data = [];
            foreach ($data as $elem) {
                $spliced_data['node' . (intval($elem[$key]) % $n_count)][] = $elem;
            }
            $data = $spliced_data;
            unset($spliced_data);
            return self::loopNodes(function (Connection $connection) use ($table, $data) {
                if (!empty($data[$connection->getName()])) {
                    foreach (array_chunk($data[$connection->getName()], 2000) as $batch) {
                        $connection->table($table)->insert($batch, null, true);
                    }
                }
            });

        } elseif (self::isGlobal($table)) {
            return self::loopNodes(function (Connection $connection) use ($table, $data) {
                foreach (array_chunk($data, 2000) as $batch) {
                    $connection->table($table)->insert($batch, null, true);
                }
            });

        } else {

            $res = [];
            foreach (array_chunk($data, 2000) as $batch) {
                $res[] = self::connection()->table($table)->insert($batch);
            }
            return count($res) == count(array_filter($res));
        }
    }

    public static function shBeginTransaction($do_master = false)
    {
        self::loopNodes(function (Connection $connection) {
            $connection->beginTransaction();
        }, $do_master);
    }

    public static function shCommit($do_master = false)
    {
        self::loopNodes(function (Connection $connection) {
            $connection->commit();
        }, $do_master);
    }

    public static function shRollback($do_master = false)
    {
        self::loopNodes(function (Connection $connection) {
            $connection->rollBack();
        }, $do_master);
    }

    /**
     * Update Multiple fields in one table
     *
     * Example:
     *
     * $table = 'users';
     * $value = [
     *      [
     *          'id' => 1,
     *          'column1' => 'value',
     *          'column2' => 'value'
     *      ] ,
     * ];
     * $index = 'id';
     *
     * @param $table
     * @param $values
     * @param $index
     * @return bool|string
     */
    public static function updateBatch($table, $values, $index)
    {
        $final = array();
        $ids = array();
        if (!count($values))
            return false;
        if (!isset($index) AND empty($index))
            return 'Select Key for Update';
        foreach ($values as $key => $val) {
            $ids[] = $val[$index];
            foreach (array_keys($val) as $field) {
                if ($field !== $index) {
                    $final[$field][] = 'WHEN `' . $index . '` = "' . $val[$index] . '" THEN "' . addslashes($val[$field]) . '" ';
                }
            }
        }
        $cases = '';
        foreach ($final as $k => $v) {
            $cases .= $k . ' = (CASE ' . implode("\n", $v) . "\n" . 'ELSE ' . $k . ' END), ';
        }
        $query = 'UPDATE ' . $table . ' SET ' . substr($cases, 0, -2) . ' WHERE ' . $index . ' IN(' . implode(',', $ids) . ')';

        return static::$instance::shsStatement($table, $query);
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