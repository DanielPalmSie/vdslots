<?php

require_once __DIR__ . '/BaseResponse.php';

class RGIAJResponse extends BaseResponse
{
    public string $response_key = 'resultadoRGIAJ';
    public string $success_code = 'COD002';
    public array $response_code = [
        'COD001' => 'The user is registered with the RGIAJ',
        'COD002' => 'The user is not registered with the RGIAJ',
        'COD006' => 'The user\'s RGIAJ status could not be verified',
    ];
}