<?php

require_once __DIR__ . '/BaseRequest.php';

class IBANRequest extends BaseRequest
{
    public array $required_fields = ['iban', 'full_name', 'user_id'];

    /**
     * @return string
     */
    public function getRequestBody(): string
    {
        return "
            <IBAN>{$this->request_data['iban']}</IBAN>
            <AssumedName>{$this->request_data['full_name']}</AssumedName>
            <DebtorReference>{$this->request_data['user_id']}</DebtorReference>
        ";
    }

    /**
     * @return string
     */
    public function getRequestXml(): string
    {
        $entrance_code = $this->request_data['entrance_code'] ?? uniqid();

        return '<IBANCheckTransactionRequest entranceCode="' . $entrance_code . '">
                ' . $this->getRequestBody() . '
            </IBANCheckTransactionRequest>';
    }

    public function getXmlWithWrapper($sender_id, $timestamp): string
    {
        $request_xml = $this->getRequestXml();

        $xml = <<<TEXT
            <IBANCheckInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="TransactionRequest"
                                mode="direct" senderID="{$sender_id}" version="1.0"
                                createDateTime="{$timestamp}" messageCount="1">
                {$request_xml}
            </IBANCheckInterface>
            TEXT;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.PHP_EOL.$xml;
    }
}