<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2/21/17
 * Time: 10:11 AM
 */

namespace App\Extensions\Database\Connection;

use App\Extensions\Database\Builder;
use App\Extensions\Database\MysqlAsync\MysqlAsync;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Events\QueryExecuted;
use App\Extensions\Database\FManager as DB;

class Connection extends BaseConnection
{
    /**
     * Get a new query builder instance.
     *
     * @return Builder
     */
    public function query()
    {
        return new Builder($this, $this->getQueryGrammar(), $this->getPostProcessor());
    }

    public function select($query, $bindings = [], $useReadPdo = true, $loopShards = false, $shParams = [], $closure = false, $isReplica = false, $isArchive = false)
    {
        if ($loopShards === false) {
            return parent::select($query, $bindings, $useReadPdo);
        }

        //TODO need to improve this to do something like in function run($query, $bindings, Closure $callback) at BaseConnection
        $start = microtime(true);

        $nodesToUse = ConnectionFactory::getNodes($isReplica, $isArchive);
        $result = MysqlAsync::executeQuery($nodesToUse, $query, $this->prepareBindings($bindings), $this->fetchMode, $shParams, $closure);

        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $bindings, $time, true);

        return $result;
    }

    public function statement($query, $bindings = [], $loopShards = false)
    {
        if ($loopShards === false) {
            return parent::statement($query, $bindings);
        }

        $start = microtime(true);

        $result = MysqlAsync::executeQuery(DB::getNodes(), $query, $this->prepareBindings($bindings), null, null, MysqlAsync::RES_STATEMENT);

        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $bindings, $time, true);

        return $result;
    }


    public function affectingStatement($query, $bindings = [], $loopShards = false)
    {
        if ($loopShards === false) {
            return parent::affectingStatement($query, $bindings);
        }

        $start = microtime(true);

        $result = MysqlAsync::executeQuery(DB::getNodes(), $query, $this->prepareBindings($bindings), null, null, MysqlAsync::RES_STATEMENT);

        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $bindings, $time, true);

        return $result;
    }

    //todo check where this is in use and why
    public function insert($query, $bindings = [], $table = null)
    {
        if (is_null($table)) {
            return parent::insert($query, $bindings);
        }

        return $this->statement($query, $bindings);
    }

    public function update($query, $bindings = [], $loopShards = false)
    {
        return $this->affectingStatement($query, $bindings, $loopShards);
    }

    public function delete($query, $bindings = [], $loopShards = false) //todo if is global should affect master
    {
        return $this->affectingStatement($query, $bindings, $loopShards);
    }

    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }

    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  float|null $time
     * @param  bool $all
     * @return void
     */
    public function logQuery($query, $bindings, $time = null, $all = false)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new QueryExecuted($query, $bindings, $time, $this));
        }

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time', 'all');
        }
    }
}
