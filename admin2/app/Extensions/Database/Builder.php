<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 9/23/16
 * Time: 12:35 PM
 */

namespace App\Extensions\Database;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Eloquent\Model;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Arr;
use Symfony\Component\Config\Definition\Exception\Exception;

class Builder extends BaseBuilder
{

    protected $loop_shards = false; //todo implement this in a proper way as I can do it automatically using the gettable method

    protected $sh_params = [];

    protected $force_closure = false;

    /** @var  Connection */
    public $connection;

    /** @var Connection[] $connections_list */
    protected $connections_list = [];

    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null, $loop_shards = false)
    {
        $this->loop_shards = $loop_shards;

        $this->connections_list = DB::getConnectionsList();

        parent::__construct($connection, $grammar, $processor);
    }

    protected function getTable($table = null)
    {
        $table = is_null($table) ? $this->from : $table;

        return preg_match('/\s/', $table) ? explode(' ', $table)[0] : $table;
    }

    /**
     * Insert logic on sharding:
     *
     * 1 No in shard config or sharding is not active do parent insert
     *
     * 2 Table is global
     *  2.1 Insert in the master and get id
     *  2.2 Loop all the nodes a insert with that id on all of them
     *  2.3 Important to detect error and update existing id on node with the same data as the master
     *      Note: according to Henrik's instructions no rollbacks will take place on inserting in global tables,
     *      instead of that we will update if a duplicate record with the same id as the master is found.
     *
     * 3 Table is sharded
     *  3.1 Check if user_id is a value
     *   3.1.1 user_id is a value automatically set the node as connection and do insert
     *   3.1.2 user_id not provided shows error
     *
     * @param array $values
     * @param bool $rollback If false rollback will not being done, update instead
     * @param bool|Connection|string $force_connection If true use the configured connection, null or false will follow the sharding logic
     * @return bool
     * @throws \Exception
     */
    public function insert(array $values, $rollback = true, $force_connection = null)
    {
        if (empty($values)) {
            return true;
        }

        if ($force_connection === true) {
            return parent::insert($values);
        }

        if (DB::isGlobal($this->getTable())) {
            if (!is_array(reset($values))) {
                $values = [$values];
            } else {
                foreach ($values as $key => $value) {
                    ksort($value);
                    $values[$key] = $value;
                }
            }

            $res = [];

            foreach ($values as $row) {
                $res[] = $this->insertGlobalRow($row, $rollback);
            }

            return count($res) == count(array_filter($res));

        } elseif (DB::isSharded($this->getTable())) { //No need to do transaction stuff as it will be inserted in one table only
            //TODO do this properly
            $key = [
                'users' => 'id',
                'actions' => 'target',
                'ip_log' => 'target'
            ];
            if (isset($key[$this->getTable()])) {
                $key = $key[$this->getTable()];
            } else {
                $key = 'user_id';
            }
            //$key = $this->getTable() == (new User())->getTable() ? 'id' : 'user_id';
            return DB::bulkInsert($this->getTable(), $key, $values);//todo this should be done in another way
        } else {
            return parent::insert($values);
        }
    }

    private function insertGlobalRow(array $row, $rollback = true, $return_id = false)
    {
        if ($this->connection->getName() !== 'default') {
            $this->connection = $this->connections_list['default'];
        }

        DB::connection()->beginTransaction();

        $row['id'] = parent::insertGetId($row);

        $candidates = [];

        $res = [];

        $do_rollback = false;

        foreach (DB::getNodesList() as $node_name) {
            if (isOnDisabledNode($node_name)) {
                continue;
            }
            DB::connection($node_name)->beginTransaction();

            try {
                $res[$node_name] = DB::connection($node_name)->insert(
                    $this->grammar->compileInsert($this, [$row]),
                    $this->cleanBindings(Arr::flatten([$row], 1))
                );
                /** @var string[] $candidates */
                $candidates[] = $node_name;

            } catch (\Exception $e) {
                if ($e->getCode() == 23000 && $rollback === false) {
                    $res[$node_name] = DB::connection($node_name)->update(
                        $this->grammar->compileUpdate($this, [$row]),
                        $this->cleanBindings(Arr::flatten([$row], 1))
                    );

                    error_log("Admin2 sharding error Key: {$row['id']}. Is duplicate key"); //todo improve logging
                } else {
                    $do_rollback = true;
                    error_log("Admin2 sharding error Key: {$row['id']}. No duplicate key");  //todo improve logging
                    break;
                }
            }
        }

        if ($rollback === true && $do_rollback === true) {
            DB::connection()->rollBack();
            foreach ($candidates as $candidate) {
                error_log("Admin2 sharding error Key: {$row['id']}. Calling rollback on $candidate"); //todo improve logging
                DB::connection($candidate)->rollBack();
            }
        } else {
            DB::connection()->commit();
            foreach ($candidates as $candidate) {
                DB::connection($candidate)->commit();
            }
        }

        $final_result = count($res) == count(array_filter($res));
        if ($return_id === true) {
            return $final_result === false ? false : $row['id'];
        } else {
            return $final_result;
        }
    }

    public function insertGetId(array $values, $sequence = null, $rollback = true, $force_connection = null)
    {
        if (empty($values)) {
            return true;
        }

        if ($force_connection === true) {
            return parent::insert($values);
        }

        if (DB::isGlobal($this->getTable())) {
            return $this->insertGlobalRow($values, true, true);
        } elseif (DB::isSharded($this->getTable())) {
            if ($this->connection->getName() === 'default') {
                $key = [
                    'users' => 'id',
                    'actions' => 'target',
                    'ip_log' => 'target'
                ];
                if (isset($key[$this->getTable()])) {
                    $key = $key[$this->getTable()];
                } else {
                    $key = 'user_id';
                }

                $table = $this->getTable();
                //$key = $table == (new User())->getTable() ? 'id' : 'user_id';

                if (empty($values[$key])) {
                    throw new Exception('Cannot save to sharded table without user id');
                }

                $resolver = Model::getConnectionResolver();

                $connection_name = $resolver->getShardSelection($values[$key], $table);

                $this->connection = $this->connections_list[$connection_name];
            }

            $id = parent::insertGetId($values, $sequence);

            if (DB::doSyncShardToMaster()) {
                $values['id'] = $id;
                $new_builder = clone $this;
                $new_builder->connection = DB::connection();
                $new_builder->insert($values, $rollback, true);
            }

            return $id;
        } else {
            return parent::insertGetId($values, $sequence);
        }
    }

    public function update(array $values, $force_connection = false)
    {
        $sql = $this->grammar->compileUpdate($this, $values);
        $bindings = $this->cleanBindings($this->grammar->prepareBindingsForUpdate($this->bindings, $values));

        if ($force_connection) {
            return $this->connection->update($sql, $bindings, false);
        }

        if (DB::isMasterAndSharded($this->getTable())) {
            throw new \Exception('Update not supported on master and sharded tables');

        } elseif (DB::isGlobal($this->getTable())) {//TODO test this

            DB::shBeginTransaction(true);
            try {
                $res = DB::loopNodes(function (Connection $connection) use ($sql, $bindings) {
                    return $connection->update($sql, $bindings, false);
                }, true);
            } catch (\Exception $e) {
                DB::shRollback(true);
                return false;
            }
            DB::shCommit(true);
            return $res;

        } elseif (DB::isSharded($this->getTable())) {
            if (DB::doSyncShardToMaster()) {
                $new_builder = clone $this;
                $new_builder->connection = DB::connection();
                $new_builder->update($values, true);
            }
            return DB::loopNodes(function (Connection $connection) use ($sql, $bindings) {
                return $connection->update($sql, $bindings, false);
            }, false);

        } else {
            return $this->connection->update($sql, $bindings, false);
        }
    }

    public function updateOrInsert(array $attributes, array $values = [], $force_connection = false)
    {
        if (DB::isGlobal($this->getTable()) || DB::isMasterAndSharded($this->getTable())) {
            throw new \Exception('UpdateOrInsert not supported on master and sharded tables');
        }

        if ($force_connection) {
            return parent::updateOrInsert($attributes, $values);
        }

        if (DB::doSyncShardToMaster()) {
            $new_builder = clone $this;
            $new_builder->connection = DB::connection();
            $new_builder->updateOrInsert($attributes, $values, true);
        }

        return parent::updateOrInsert($attributes, $values);
    }

    public function delete($id = null, $force_connection = false)
    {
        if (!is_null($id)) {
            $this->where($this->from . '.id', '=', $id);
        }

        if (DB::isGlobal($this->getTable()) || DB::isMasterAndSharded($this->getTable())) {
            DB::shBeginTransaction(true);
            try {
                $sql = $this->grammar->compileDelete($this);
                $bindings = $this->getBindings();
                $res = DB::loopNodes(function (Connection $connection) use ($sql, $bindings) {
                    return $connection->delete($sql, $bindings);
                }, true);
            } catch (\Exception $e) {
                DB::shRollback(true);
                return false;
            }
            DB::shCommit(true);
            return $res;
        } elseif (DB::isSharded($this->getTable())) {
            $loop = true;
            if ($force_connection) {
                $loop = false;
            }

            if (!$force_connection && DB::doSyncShardToMaster()) {
                $new_builder = clone $this;
                $new_builder->connection = DB::connection();
                $new_builder->delete($id, true);
            }
        } else {
            $loop = false;
        }

        return $this->connection->delete($this->grammar->compileDelete($this), $this->getBindings(), $loop);
    }

    /**
     * Configure sharding on the query builder
     *
     * @param null|string $table
     * @return $this
     */
    public function shs($table = null)
    {
        if (is_null($table) && empty($this->getTable())) {
            return $this;
        }

        $table = !empty($table) ? $table : $this->getTable();

        if (DB::isSharded($table) && !DB::isMasterAndSharded($table)) {
            $this->loop_shards = true;
        } elseif (DB::isGlobal($table) || DB::isMasterAndSharded($table)) {
            $this->loop_shards = true;
        }

        return $this;
    }

    public function count($columns = '*')
    {
        if ($this->loop_shards !== false)
            $this->force_closure = MysqlAsync::RES_AGGREGATE;

        $res = parent::count();
        $this->force_closure = false;
        return $res;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect()
    {
        return $this->connection->select($this->toSql(), $this->getBindings(), !$this->useWritePdo, $this->loop_shards, $this->sh_params, $this->force_closure);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int $value
     * @return $this
     */
    public function offset($value)
    {
        if ($this->loop_shards !== false) {
            $this->sh_params['offset'] = $value;
        } else {
            parent::offset($value);
        }
        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     * @return $this
     */
    public function limit($value)
    {
        if ($this->loop_shards !== false) {
            $this->sh_params['length'] = $value;
        } else {
            parent::limit($value);
        }
        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->loop_shards !== false) {
            $this->sh_params['sort'][] = [
                'column' => $column,
                'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
            ];
        } else {
            parent::orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        if ($this->loop_shards !== false) {
            foreach ($groups as $group) {
                $this->sh_params['groups'] = array_merge(
                    (array)$this->sh_params['groups'],
                    array_wrap($group)
                );
            }
        }

        parent::groupBy(...$groups);

        return $this;
    }

    public function groupByRaw($sql, $do_parent = false)
    {
        if ($do_parent === true) {
            parent::groupBy(DB::raw($sql));
        } else {
            throw new \Exception("groupByRaw not supported when not using the parent function on cross-shard queries.");
        }
    }

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return $this
     * @throws \Exception
     */
    public function orderByRaw($sql, $bindings = [])
    {
        if ($this->loop_shards !== false) {
            throw new \Exception("orderByRaw not supported on cross-shard queries.");
        } else {
            parent::orderByRaw($sql, $bindings);
        }

        return $this;
    }

}
