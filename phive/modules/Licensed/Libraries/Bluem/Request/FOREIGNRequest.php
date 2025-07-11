<?php

require_once __DIR__ . '/BaseRequest.php';

class FOREIGNRequest extends BaseRequest
{
    public array $required_fields = ['dob', 'lastname'];

    public function getRequestBody(): string
    {
        return "
            <ForeignRequest>
                <FirstNames>{$this->request_data['firstname']}</FirstNames>
                <SurnamePrefix>{$this->request_data['last_name_prefix']}</SurnamePrefix>
                <Surname>{$this->request_data['lastname']}</Surname>
                <BirthDate>{$this->request_data['dob']}</BirthDate>
                <BirthPlace>{$this->request_data['birth_location']}</BirthPlace>
            </ForeignRequest>
        ";
    }
}