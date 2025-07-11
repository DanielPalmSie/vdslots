<?php

namespace Videoslots\Events\RabbitMQ;

use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConnectionFactory
{
    public static function createConnection(
        $host,
        $port,
        $user,
        $password,
        $vhost = '/',
        $insist = false,
        $login_method = 'AMQPLAIN',
        $login_response = null,
        $locale = 'en_US',
        $connection_timeout = 3.0,
        $read_write_timeout = 3.0,
        $context = null,
        $keepalive = false,
        $heartbeat = 0,
        $channel_rpc_timeout = 0.0,
        $ssl_protocol = null
    ) {
        $use_ssl = phive('Distributed')->getSetting('use_ssl');
        if ($use_ssl) {
            $options = [
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => $connection_timeout,
                'read_write_timeout' => $read_write_timeout,
                'keepalive' => $keepalive,
                'heartbeat' => $heartbeat,
                // 'channel_rpc_timeout' =>
            ];

            return new AMQPSSLConnection(
                $host,
                $port,
                $user,
                $password,
                $vhost,
                ['verify_peer_name' => false],
                $options
            );
        } else {
            return new AMQPStreamConnection(
                $host,
                $port,
                $user,
                $password,
                $vhost,
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $connection_timeout,
                $read_write_timeout,
                null,
                $keepalive,
                $heartbeat
            );
        }
    }
}
