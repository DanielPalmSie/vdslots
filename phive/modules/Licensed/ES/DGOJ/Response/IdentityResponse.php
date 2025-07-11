<?php

require_once __DIR__ . '/BaseResponse.php';

class IdentityResponse extends BaseResponse
{
    const RESPONSE_CODE_DECEASED = 'COD007';
    public string $response_key = 'resultadoIdentidad';
    public string $success_code = 'COD003';
    public array $response_code = [
        'COD003' => 'The identity of the user is correct',
        'COD004' => 'The identity of the user is incorrect',
        'COD007' => 'The user was identified in the Civil Registry as deceased in previous dates to receive the request',
        'COD104' => 'The identity is incorrect and also the player is an underage',
        'COD005' => 'It was not possible to verify the identity of the user',
        // Others - these will come up in both Identity and RGIAJ response
        // Handling these only here on Identity will be enough
        'COD901' => 'Incorrect data format - invalid DNI',
        'COD902' => 'The request contains invalid characters in *Name/surname1/surname2',
        'COD903' => 'Incorrect data format - The *Name/surname1/surname2 are mandatory',
        'COD904' => 'Incorrect data format - Birth date before 1900',
        'COD905' => 'Incorrect data format - Incorrect support number',
        'COD906' => 'Incorrect data format - With NIF it is not possible to send a Support number',
        'COD907' => 'Length of name or surnames exceeds 40 characters',
    ];
}