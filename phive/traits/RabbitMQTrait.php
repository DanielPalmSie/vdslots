<?php

/**
 * Contains common functions shared by the Queued and EventQueued classes.
 */
trait RabbitMQTrait
{
    /**
     * Gets the connection to be used based on if we are using haproxy, standard connection or not
     * @return array
     */
    private function getServerConfig()
    {
        $proxy_enabled = $this->proxy_config && $this->proxy_config['enabled'];
        $host = $proxy_enabled ? $this->proxy_config['host'] : $this->config['host'];
        $port = $proxy_enabled ? $this->proxy_config['port'] : $this->config['port'];
        $user = $this->config['user'];
        $psw = $this->config['pwd'];
        $vhost = $proxy_enabled ? $this->proxy_config['vhost'] : $this->config['vhost'];

        return  [
            'host' => $this->forced_host ?: $host,
            'port' => $this->forced_port ?: $port,
            'user' => $user,
            'pwd' => $psw,
            'vhost' => $vhost ?? "/",
        ];
    }
}
