<?php
namespace IT\Abstractions;

/**
 * Interface InterfaceClient
 * @package IT\Abstractions
 */
interface InterfaceClient
{
    public function __construct(array $configurations);

    /**
     * @param array $configurations
     * @return void
     */
    public function setConfigurations(array $configurations);

    /**
     * Return data used to compose the request
     * @return array
     */
    public function getPayloadRequest(): array;

    /**
     * @param array $payload
     * @return mixed
     */
    public function exec(array $payload);

    /**
     * Return client name
     * @return mixed
     */
    public function getProtocolVersion();
}