<?php
require_once __DIR__ . '/../phive.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Videoslots\Events\RabbitMQ\RabbitMQConnectionFactory;

/*
 *  @deprecated Use Site/Publisher and Site/Consumer
 */

$script  = array_shift($_SERVER['argv']);
$timeout = array_shift($_SERVER['argv']);
$ch      = array_shift($_SERVER['argv']);
$mq_conf = phive()->getSetting('mq-config');
$use_proxy = !empty($mq_conf['proxy_enabled']) &&  $mq_conf['proxy_enabled'] === true;
$host = $use_proxy ? ($mq_conf['proxy_host'] ?? $mq_conf['host']) : $mq_conf['host'];
$port = $use_proxy ? ($mq_conf['proxy_port'] ?? 5673): 5672;
$vhost = $mq_conf['vhost'] ?? "/";
$connection = RabbitMQConnectionFactory::createConnection($host, $port, $mq_conf['user'], $mq_conf['pwd'], $vhost);

$channel    = $connection->channel();
$channel->queue_declare('phive-'.$ch, false, false, false, false);


$module  = array_shift($_SERVER['argv']);
$method  = array_shift($_SERVER['argv']);

//Do we have the args stored in Redis with an uuid? If so we pass it on to the qserver.
if(phive()->isUuid($_SERVER['argv'][0])){
    $json   = json_encode([$timeout, $module, $method, [$_SERVER['argv'][0]]]);
}else{
    $args    = array_map(function($arg){ return $arg == 'na' ? '' : $arg;  }, $_SERVER['argv']);
    $json   = json_encode([$timeout, $module, $method, $args]);
}

$msg    = new AMQPMessage($json);
$channel->basic_publish($msg, '', 'phive-'.$ch);

$channel->close();
$connection->close();


