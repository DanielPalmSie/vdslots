<?php

use RobRichards\WsePhp\WSSESoap;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SignedSoap extends SoapClient
{
    /** @var string|mixed $private_key_location Location of private key on disk */
    private string $private_key_location;
    /** @var string|mixed $certificate_location Location of certificate on disk */
    private string $certificate_location;

    /**
     * SignedSoap constructor.
     * @param $wsdl
     * @param array|null $options
     * @throws SoapFault
     */
    public function __construct($wsdl, array $options = null)
    {
        parent::__construct($wsdl, $options);

        $this->private_key_location = @$options['ssl']['private_key'] ?? '';
        $this->certificate_location = @$options['ssl']['certificate'] ?? '';
    }

    /**
     * Overwrite parent __doRequest to handle the signing
     * @see https://github.com/robrichards/wse-php
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param false $oneWay
     * @return string|null
     * @throws Exception
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = false): ?string
    {
        $doc = new DOMDocument('1.0');
        $doc->loadXML($request);

        $objWSSE = new WSSESoap($doc);

        /* add Timestamp with no expiration timestamp */
        $objWSSE->addTimestamp();

        /* create new XMLSec Key using AES256_CBC and type is private key */
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));

        /* load the private key from file - last arg is bool if key in file (true) or is string (false) */
        $objKey->loadKey($this->private_key_location, true);

        /* Sign the message - also signs appropriate WS-Security items */
        $options = array("insertBefore" => false);
        $objWSSE->signSoapDoc($objKey, $options);

        /* Add certificate (BinarySecurityToken) to the message */
        $token = $objWSSE->addBinaryToken(file_get_contents($this->certificate_location));

        /* Attach pointer to Signature */
        $objWSSE->attachTokentoSig($token);

        return parent::__doRequest($objWSSE->saveXML(), $location, $action, $version, $oneWay);
    }
}
