<?php

require_once __DIR__ . '/BaseResponse.php';

class ServiceResponse extends BaseResponse
{
    public string $key_code = 'faultcode';
    public string $key_description = 'faultstring';
    public array $response_code = [
        'ERR001' => 'Technical error',
        'ERR002' => 'Invalid Gaming Operator',
        'ERR003' => 'Invalid request',
        'Client' => 'Invalid request',
        '0' => 'Service exception',
    ];
}