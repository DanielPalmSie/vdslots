<?php

require_once __DIR__ . '/BaseRequest.php';

class BSNRequest extends BaseRequest
{
    public array $required_fields = ['bsn', 'dob', 'lastname'];
    
    public function getRequestBody(): string
    {
        return "
            <BSNRequest>
                <BSN>{$this->request_data['bsn']}</BSN>
                <SurnamePrefix>{$this->request_data['last_name_prefix']}</SurnamePrefix>
                <Surname>{$this->request_data['lastname']}</Surname>
                <BirthDate>{$this->request_data['dob']}</BirthDate>
            </BSNRequest>
        ";
    }
}