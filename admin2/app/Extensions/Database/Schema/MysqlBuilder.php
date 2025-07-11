<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 5/10/17
 * Time: 2:24 PM
 */

namespace App\Extensions\Database\Schema;

use App\Extensions\Database\Connection\Connection;
use Illuminate\Database\Schema\MySqlBuilder as BaseBuilder;
use App\Extensions\Database\FManager as DB;

class MysqlBuilder extends BaseBuilder
{
    /** @var  Connection $connection */
    protected $connection;

    /**
     * Create a new database Schema manager.
     *
     * @param  Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param  string $table
     * @param  \Closure|null $callback
     * @return Blueprint
     */
    protected function createBlueprint($table, \Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback);
        }

        return new Blueprint($table, $callback);
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param  Blueprint $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     * @param string|bool $connection_name Connection name to do only one
     * @return bool
     */
    public function hasTable($table, $connection_name = null)
    {
        $callback = function ($connection) use ($table) {
            return count($connection->select(
                    $this->grammar->compileTableExists(), [$connection->getDatabaseName(), $connection->getTablePrefix() . $table]
                )) > 0;
        };

        if (!empty($connection_name) && is_string($connection_name)) {
            return $callback(DB::connection($connection_name));
        }

        if (DB::isSharded($table) || DB::isGlobal($table)) {
            return DB::loopNodes($callback, DB::isGlobal($table));
        } else {
            return $callback(DB::connection());
        }
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string $table
     * @return array
     */
    public function getColumnListing($table)
    {
        if (DB::isSharded($table)) {
            $connection = DB::connection(DB::getEnabledShardSelection(1));
        } else {
            $connection = DB::connection();
        }

        $results = $connection->select(
            $this->grammar->compileColumnListing(), [$connection->getDatabaseName(), $connection->getTablePrefix() . $table]
        );

        return $connection->getPostProcessor()->processColumnListing($results);
    }
}