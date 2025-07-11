<?php

require_once __DIR__ . '/BaseRequest.php';

class CRUKSRequest extends BaseRequest
{
    public array $required_fields = ['cruks_code'];

    public function getRequestBody(): string
    {
        return "
            <CRUKSRequest>
                <RequestCRUKSCode>{$this->request_data['cruks_code']}</RequestCRUKSCode>
            </CRUKSRequest>
        ";
    }
}