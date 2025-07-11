<?php
/**
 */

namespace App\Providers;

use App\Extensions\Database\FManager as Capsule;
use Illuminate\Container\Container as CapsuleContainer;
use Illuminate\Events\Dispatcher;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class DatabaseServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * Register the Capsule service.
     *
     * @param PimpleContainer $app
     * @return void
     **/
    public function register(PimpleContainer $app)
    {
        $app['capsule.connection_defaults'] = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => null,
            'username' => null,
            'password' => null,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => null,
            'logging' => false,
            'node' => false
        ];

        $app['capsule.global'] = true;
        $app['capsule.eloquent'] = true;

        $app['capsule.container'] = function () {
            return new CapsuleContainer();
        };

        $app['capsule.dispatcher'] = function (PimpleContainer $app) {
            return new Dispatcher($app['capsule.container']);
        };

        $app['capsule'] = function (PimpleContainer $app) {
            $capsule = new Capsule(
                $app['capsule.container'],
                $app['capsule.vs.db']['shards'],
                [
                    'global' => $app['capsule.vs.db']['global_tables'],
                    'sharded' => $app['capsule.vs.db']['sharded_tables'],
                    'master.sharded' => $app['capsule.vs.db']['master_and_sharded_tables']
                ]
            );
            $capsule->setEventDispatcher($app['capsule.dispatcher']);

            if ($app['capsule.global']) {
                $capsule->setAsGlobal();
            }

            if ($app['capsule.eloquent']) {
                $capsule->bootEloquent();
            }

            if (!isset($app['capsule.connections'])) {
                $app['capsule.connections'] = [
                    'default' => (isset($app['capsule.connection']) ? $app['capsule.connection'] : []),
                ];
            }

            $nodes_list = [];
            $replica_nodes_list = [];
            $archive_nodes_list = [];

            foreach ($app['capsule.connections'] as $connection => $options) {
                if ($connection === 'nodes' && count($options) > 0) {
                    $nodes_list = $this->addConnectionForNodes($options, 'node', 'shards', $app, $capsule);
                }
                if ($connection === 'replica_nodes' && count($options) > 0) {
                    $replica_nodes_list = $this->addConnectionForNodes($options, 'replica_node', 'shards_replica', $app, $capsule);
                }

                if ($connection === 'archive_nodes' && count($options) > 0) {
                    $archive_nodes_list = $this->addConnectionForNodes($options, 'archive_node', 'shards_archive', $app, $capsule);
                }

                $options = array_replace($app['capsule.connection_defaults'], $options);
                $logging = $options['logging'];
                unset($options['logging']);
                if($connection != 'nodes' && $connection != 'replica_nodes' && $connection != 'archive_nodes' && $options != false) {
                    $capsule->addConnection($options, $connection);
                    $this->queryLogging($app, $logging, $capsule, $connection);
                }
            }

            if (!empty($app['capsule.vs.db'])) {
                //TODO this is duplicated already done in the constructor
                if($app['capsule.vs.db']['sharding_status']) {
                    $capsule->setShardingConfig([
                        'nodes' => $app['capsule.vs.db']['shards'],
                        'nodes.list' => $nodes_list,
                        'replica_nodes' => $app['capsule.vs.db']['shards_replica'],
                        'replica_nodes.list' => $replica_nodes_list,
                        'archive_nodes' => $app['capsule.vs.db']['shards_archive'],
                        'archive_nodes.list' => $archive_nodes_list,
                        'count' => count($app['capsule.vs.db']['shards']),
                        'status' => $app['capsule.vs.db']['sharding_status'],
                        'sync.shard.to.master' => $app['capsule.vs.db']['sync_shard_to_master'],
                        'global.tables' => $app['capsule.vs.db']['global_tables'], //TODO duplicated is this really necessary???
                        'sharded.tables' => $app['capsule.vs.db']['sharded_tables'], //TODO duplicated is this really necessary???
                        'master.sharded.tables' => $app['capsule.vs.db']['master_and_sharded_tables'], //TODO duplicated is this really necessary???
                        'masters' => $app['capsule.masters']
                    ]);
                }


                if(!empty($app['capsule.vs.db']['migration_driver'])){
                    $capsule->setupMigrationDriver(
                        $app['capsule.vs.db']['migration_driver'],
                        $app['capsule.vs.db']['migration_driver_options'] ?? []
                    );
                }
            }

            return $capsule;
        };
    }

    /**
     * Enable or disable query logging
     * @param $app
     * @param $loggingIsEnabled
     * @param $capsule
     * @param $connection
     * @return void
     */
    protected function queryLogging($app, $loggingIsEnabled, $capsule, $connection)
    {
        if ($loggingIsEnabled || $app['capsule.enable.log.all']) {
            $capsule->connection($connection)->enableQueryLog();
        } else {
            $capsule->connection($connection)->disableQueryLog();
        }
    }

    /**
     * Function for adding node and replica_node connections
     * @param $node_connections
     * @param $nodename_prefix
     * @param $shards_key
     * @param $app
     * @param $capsule
     * @return array
     */
    protected function addConnectionForNodes($node_connections, $nodename_prefix, $shards_key, $app, $capsule)
    {
        $nodes_list = [];
        foreach ($node_connections as $main_node => $node_option) {
            if (isOnDisabledNode($main_node)) {
                continue;
            }
            $node_option = array_replace($app['capsule.connection_defaults'], $node_option);
            $node_option[$nodename_prefix.'_id'] = $main_node;
            $logging = $node_option['logging'];
            unset($node_option['logging']);
            $node_option['is_'.$nodename_prefix] = true;
            $node_option[$nodename_prefix.'_count'] = count($app['capsule.vs.db'][$shards_key]);
            $nodes_list[] = $main_node = $nodename_prefix . $main_node;
            $capsule->addConnection($node_option, $main_node);
            $this->queryLogging($app, $logging, $capsule, $main_node);
        }

        return $nodes_list;
    }
    /**
     * Boot the Capsule service.
     *
     * @param Application $app
     * @return void
     **/
    public function boot(Application $app)
    {
        if ($app['capsule.eloquent']) {
            $app->before(function () use ($app) {
                $app['capsule'];
            }, Application::EARLY_EVENT);
        }
    }

}
