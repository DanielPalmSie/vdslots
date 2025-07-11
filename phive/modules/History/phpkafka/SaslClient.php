<?php

/*
 * code based in https://github.com/swoole/phpkafka/pull/74, delete when it's integrated
 */

declare(strict_types=1);

namespace History\phpkafka;

use longlang\phpkafka\Protocol\ErrorCode;
use longlang\phpkafka\Protocol\SaslAuthenticate\SaslAuthenticateRequest;
use longlang\phpkafka\Protocol\SaslAuthenticate\SaslAuthenticateResponse;
use longlang\phpkafka\Protocol\SaslHandshake\SaslHandshakeRequest;
use longlang\phpkafka\Protocol\SaslHandshake\SaslHandshakeResponse;

class SaslClient extends \longlang\phpkafka\Client\SyncClient
{

    protected function sendAuthInfo(): void
    {
        $class = $this->getSaslDriver();
        if (is_null($class)) {
            return;
        }

        $handshakeRequest = new SaslHandshakeRequest();
        $handshakeRequest->setMechanism($class->getName());
        $correlationId = $this->send($handshakeRequest);
        /** @var SaslHandshakeResponse $handshakeResponse */
        $handshakeResponse = $this->recv($correlationId);
        ErrorCode::check($handshakeResponse->getErrorCode());
        $authenticateRequest = new SaslAuthenticateRequest();
        $authenticateRequest->setAuthBytes($class->getAuthBytes());
        $correlationId = $this->send($authenticateRequest);
        /** @var SaslAuthenticateResponse $authenticateResponse */
        $authenticateResponse = $this->recv($correlationId);
        ErrorCode::check($authenticateResponse->getErrorCode());
    }

    protected function getSaslDriver()
    {
        $config = $this->getConfig()->getSasl();
        if (empty($config['type'])) {
            return null;
        }
        $sasl = new $config['type']($this->getConfig());

        if (method_exists($sasl, 'setHost')) {
            $sasl->setHost($this->socket->getHost());
        }

        return $sasl;
    }
}
