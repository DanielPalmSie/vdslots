<?php

namespace App\Extensions\Database\Connection;

use App\Extensions\Database\Connectors\MysqlConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Connectors\SqlServerConnector;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use InvalidArgumentException;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Extensions\Database\ArchiveFManager as ArchiveDB;

use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;

class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Create a new connection instance.
     *
     * @param  string   $driver
     * @param  \PDO|\Closure     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, array_merge($config, [
                    'nodes' => $this->container['config']['database.nodes'],
                    'global' => $this->container['config']['database.tables.global'],
                    'sharded' => $this->container['config']['database.tables.sharded'],
                    'sharded.global' => $this->container['config']['database.tables.master.sharded']
                ]));
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }

    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        switch ($config['driver']) {
            case 'mysql':
                return new MysqlConnector;
            case 'pgsql':
                return new PostgresConnector;
            case 'sqlite':
                return new SQLiteConnector;
            case 'sqlsrv':
                return new SqlServerConnector;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    public static function getNodes(bool $isReplica, bool $isArchive)
    {
        if (!$isReplica && !$isArchive) {
            return DB::getNodes();
        } elseif ($isArchive) {
            return ArchiveDB::getNodes();
        } else {
            return ReplicaDB::getNodes();
        }
    }
}
