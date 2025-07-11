<?php
/**
 * This class will be responsible of sharding logic related to eloquent models extension
 */

namespace App\Extensions\Database\Eloquent;

use App\Extensions\Database\DatabaseManager;
use App\Extensions\Database\FManager as DB;
use App\Models\User;
use Illuminate\Database\Eloquent\Model as BaseModel;
use App\Extensions\Database\Eloquent\Builder as EloquentBuilder;
use App\Extensions\Database\Builder as QueryBuilder;

class Model extends BaseModel
{

    protected static $default_shard_key = 'user_id';
    /**
     * @inheritdoc
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return EloquentBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    public function getShardKeyId()
    {
        return $this->{$this->getShardKeyName()};
    }

    public function getShardKeyName()
    {
        return self::$default_shard_key;
    }

    /**
     * Behaviour:
     *  - Table is global, we hit a random connection selected between the nodes and including the master
     *  - Table is sharded, we do a cross-shard by default
     *
     * @return QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $loop_shards = false;

        /** @var DatabaseManager $conn_resolver */
        $conn_resolver = $this->getConnectionResolver();
        if ($conn_resolver->getShardingStatus()) {
            if (self::isGlobal()) {
                $conn = $conn_resolver->connection($conn_resolver->getGlobalSelection());
            } elseif (self::isSharded() && !self::isMasterAndSharded() && ($this->getConnectionName() == 'default' || is_null($this->getConnectionName()))) {
                $loop_shards = true;
                $conn = $this->getConnection();
            } else {
                $conn = $this->getConnection();
            }
        } else {
            $conn = $this->getConnection();
        }

        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor(), $loop_shards);
    }

    /**
     * Create a new model instance that is existing.
     *
     * If it is a sharded table connection is set to its node depending on the key attribute (id on users and user_id on the rest).
     *
     * @param  array $attributes
     * @param  string|null $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $attributes = (array)$attributes;

        /** @var DatabaseManager $conn_res */
        $conn_res = $this->getConnectionResolver();
        $table = (new static())->getTable();

        if ($conn_res->getShardingStatus() && $conn_res->isSharded($table)) {
            //$key = $table == (new User())->getTable() ? 'id' : 'user_id';
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
            if (!empty($attributes[$key]) && $attributes[$key] != $model->{$key}) {
                $model->setConnection($conn_res->getShardSelection($attributes[$key], $table));
            } else {
                $model->setConnection($connection ?: $this->getConnectionName());
            }

        } else {
            $model->setConnection($connection ?: $this->getConnectionName());
        }

        $model->setRawAttributes($attributes, true);

        return $model;
    }

    /**
     * This is overridden as you cannot set a node as a connection when the table is not sharded.
     *
     * @param  string $name
     * @return $this
     */
    public function setConnection($name)
    {
        /** @var DatabaseManager $conn_res */
        $conn_res = $this->getConnectionResolver();
        if (!$conn_res->isMasterConnection($name)) {
            if (!$conn_res->getShardingStatus() || !self::isSharded()) {
                $this->connection = null;
                return $this;
            }
        }

        $this->connection = $name;
        return $this;
    }

    public function setReplicaConnectionName($key)
    {
        $conn_res = $this->getConnectionResolver();
        $countShards = $conn_res->getShardsCount() ?? 0;
        $shardingStatus = $conn_res->getShardingStatus() ?? false;

        if ((!empty($countShards) && $countShards > 0) && $shardingStatus) {
            return 'replica_node' . (intval($key) % $countShards);
        }

        return 'replica';
    }

    /**
     * @param array|string $relations
     * @return mixed
     */
    public static function with($relations)
    {
        return (new static)->shs()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    /**
     *
     * @param $key
     * @param $relations
     * @return mixed
     */
    public static function shWith($key, $relations)
    {
        if (is_string($relations)) {
            $args_array = func_get_args();
            $key = array_shift($args_array);
            $relations = $args_array;
        }

        return (new static)::sh($key)->newQuery()->with($relations);
    }

    /**
     * @param mixed $key Sharding key
     * @param boolean $isReplica check of connection will be to replica database or not. By default it is false
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function sh($key, $isReplica = false)
    {
        if ($isReplica === false || replicaDatabaseSwitcher() == false) {
            return self::on(self::getConnectionResolver()->getShardSelection($key, (new static())->getTable()));
        }
        return self::on(self::getConnectionResolver()->getReplicaShardSelection($key, (new static())->getTable()));
    }

    /**
     * @return Builder
     */
    public static function shs()
    {
        $instance = new static;

        return $instance->newQuery()->shs($instance->getTable());
    }

    public static function bulkInsert(array $data, $key = 'user_id', $connection = null)
    {
        if (empty($data)) {
            return true;
        }
        return DB::bulkInsert((new static)->getTable(), $key, $data, $connection);
    }

    public static function isSharded()
    {
        return DB::isSharded((new static())->getTable());
    }

    public static function isGlobal()
    {
        return DB::isGlobal((new static())->getTable());
    }

    public static function isMasterAndSharded()
    {
        return DB::isMasterAndSharded((new static())->getTable());
    }

    /**
     * Get the list of columns names for the related table.
     * @return array
     */
    public function getTableColumns()
    {
        /** @var DatabaseManager $conn_resolver */
        $conn_resolver = $this->getConnectionResolver();
        if ($conn_resolver->getShardingStatus() || self::isSharded()) {
            $this->setConnection($conn_resolver->getShardSelection(0, (new static)->getTable()));
        }
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
}
