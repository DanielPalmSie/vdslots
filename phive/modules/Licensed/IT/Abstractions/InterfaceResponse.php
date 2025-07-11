<?php
namespace IT\Abstractions;

use IT\Abstractions\AbstractRequest;

/**
 * Interface InterfaceResponse
 * @package IT\Pacg\Responses
 */
interface InterfaceResponse
{
    /**
     * InterfaceResponse constructor.
     * @param AbstractRequest $request
     */
    public function __construct(AbstractRequest $request);

    /**
     * @return int
     */
    public function getCode(): ?int;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return array
     */
    public function getResponseBody(): array;

    /**
     * @return array
     */
    public function toArray(): array;
}