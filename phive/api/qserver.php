<?php
require_once __DIR__ . '/../phive.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Videoslots\Events\RabbitMQ\RabbitMQConnectionFactory;

/*
 *  @deprecated Use Site/Publisher and Site/Consumer
 */

$mq_conf = phive()->getSetting('mq-config');
$vhost = $mq_conf['vhost'] ?? "/";
$connection = RabbitMQConnectionFactory::createConnection($mq_conf['host'], 5672, $mq_conf['user'], $mq_conf['pwd'], $vhost);

$channel    = $connection->channel();

foreach(phive()->getSetting('mq-channels') as $ch)
    $channel->queue_declare('phive-'.$ch, false, false, false, false);

$callback = function($msg) {
    $arr     = json_decode($msg->body, true);
    $timeout = array_shift($arr);
    $module  = array_shift($arr);
    $func    = array_shift($arr);
    $args    = array_shift($arr);

    //Is the first argument an uuid? If that is the case we pass it on to pexec.php
    $args = phive()->isUuid($args[0]) ? $args[0] : implode(' ', $args);

    //Shell execution is a must, otherwise we would have to restart the qserver everytime we update Phive.
    shell_exec('php '.__DIR__."/pexec.php 50-$timeout $module $func $args");
};

foreach(phive()->getSetting('mq-channels') as $ch)
    $channel->basic_consume('phive-'.$ch, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
