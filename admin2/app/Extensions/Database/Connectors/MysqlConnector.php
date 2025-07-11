<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 6/23/17
 * Time: 12:00 PM
 */

namespace App\Extensions\Database\Connectors;

use Illuminate\Database\Connectors\MySqlConnector as BaseMysqlConnector;

class MysqlConnector extends BaseMysqlConnector
{

    public function connect(array $config)
    {
        $connection = parent::connect($config);

        //Set autoincrement only on nodes
        if ($config['is_node'] == true) {
            $connection->prepare("set auto_increment_increment = {$config['node_count']}")->execute();
            $connection->prepare('set auto_increment_offset = '. ($config['node_id'] + 1))->execute();
        }

        //Set autoincrement only on replica nodes
        if ($config['is_replica_node'] == true) {
            $connection->prepare("set auto_increment_increment = {$config['replica_node_count']}")->execute();
            $connection->prepare('set auto_increment_offset = '. ($config['replica_node_id'] + 1))->execute();
        }

        return $connection;
    }
}
