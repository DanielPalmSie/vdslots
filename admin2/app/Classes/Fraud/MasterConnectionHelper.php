<?php

declare(strict_types=1);

namespace App\Classes\Fraud;

use App\Extensions\Database\FManager;
use App\Extensions\Database\ReplicaManager;
use App\Extensions\Database\ArchiveManager;
use Illuminate\Database\ConnectionInterface;
use Pimple\Container;

final class MasterConnectionHelper
{
    /**
     * @var \Pimple\Container
     */
    private Container $app;

    /**
     * @param \Pimple\Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Depending on if replica is enabled or not we return either name of `default` masterDB,
     * either name of `replica` masterDB.
     *
     * @return string
     */
    public function getMasterDatabaseName(): string
    {
        $connectionName = $this->getConnectionName();

        return $this->app['capsule.connections'][$connectionName]['database'];
    }

    /**
     * Depending on if replica is enabled or not return connection either to masterDb on `default` connection, either
     * connection to masterDB on `replica`.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getMasterConnection(): ConnectionInterface
    {
        if ($this->isReplicaEnabled()) {
            return ReplicaManager::getMasterConnection();
        }

        if ($this->isArchiveEnabled()) {
            return ArchiveManager::getMasterConnection();
        }

        return FManager::getMasterConnection();
    }

    /**
     * Returns connectionName based on if replica is enabled or not.
     *
     * @return string
     */
    private function getConnectionName(): string
    {
        return $this->isReplicaEnabled() ? 'replica' : 'default';
    }

    /**
     * If there is no DB_REPLICA_HOST in .env, instead of replica configuration `false` value would be placed in
     * `capsule.connection.replica` path.
     *
     * @return bool
     */
    private function isReplicaEnabled(): bool
    {
        return $this->app['capsule.connections']['replica'] !== false;
    }

    private function isArchiveEnabled(): bool
    {
        return $this->app['capsule.connections']['videoslots_archived'] !== false;
    }
}
