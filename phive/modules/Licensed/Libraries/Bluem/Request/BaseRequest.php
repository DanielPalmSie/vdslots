<?php

abstract class BaseRequest
{

    public array $request_data;

    public array $required_fields = [];

    private array $valid_keys = [
        'user_id',
        'lastname',
        'dob',
        'bsn',
        'firstname',
        'last_name_prefix',
        'birth_location',
        'cruks_code'
    ];

    private array $missing_fields = [];

    abstract public function getRequestBody(): string;

    public function __construct($request_data = [])
    {
        foreach ($request_data as $key) {
            if (!in_array($key, $this->valid_keys, true)) {
                unset($request_data[$key]);
            }
        }

        $this->request_data = $request_data;
    }

    public function isValidRequest(): bool
    {
        foreach ($this->required_fields as $field) {
            if (!isSet($this->request_data[$field])) {
                $this->missing_fields[] = $field;
            }
        }

        return empty($this->missing_fields);
    }

    public function getMissingFields($as_string = true)
    {
        if ($as_string) {
            return implode(',', $this->missing_fields);
        }
        return $this->missing_fields;
    }

    public function getRequestXml(): string
    {
        $user_id = $this->request_data['user_id'];
        $user_id = $user_id ? "<DebtorReference>$user_id</DebtorReference>" : "";
        $entrance_code = $this->request_data['entrance_code'] ?? uniqid();

        return '
            <CRUKSCheckTransactionRequest entranceCode="' . $entrance_code . '">
                ' . $this->getRequestBody() . '
                ' . $user_id . '
            </CRUKSCheckTransactionRequest>
        ';
    }
}