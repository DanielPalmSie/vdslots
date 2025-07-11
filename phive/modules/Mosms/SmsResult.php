<?php

namespace Mosms;

abstract class SmsResult
{
    protected string $response_body;

    protected int $response_status;

    public function __construct(?string $response_body, ?int $response_status)
    {
        $this->response_body = $response_body ?? '';
        $this->response_status = $response_status ?? -1;
    }

    abstract public function isSuccess(): bool;

    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }

    public function getResponseBody(): string
    {
        return $this->response_body;
    }

    public function getResponseStatus(): int
    {
        return $this->response_status;
    }
}
