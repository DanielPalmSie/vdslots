<?php
namespace IT\Pacg\Client;

use IT\Abstractions\InterfaceClient;
use DOMDocument;
use RobRichards\WsePhp\WSSESoap;
use RobRichards\WsePhp\WSSESoapServer;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use IT\Pacg\Codes\ReturnCode as PacgReturnCode;
use Exception;

/**
 * Class PacgClient
 */
class PacgClient extends \SoapClient implements InterfaceClient
{
    /**
     * The PACG Protocol version currently in use
     */
    private const DEFAULT_PROTOCOL_VERSION = 2.2;

    /**
     * PacgClient constructor.
     * @param array $configurations
     * @throws \SoapFault
     */
    public function __construct(array $configurations)
    {
        if ($configurations['disable_strict_ssl']) {
            $configurations['options']['stream_context'] = $this->getContextConfiguration();
        }

        PacgClient::setPrivateKey($configurations['private_key'] ?? '');
        PacgClient::setSigningCertificatePath($configurations['signing_certificate'] ?? '');
        PacgClient::setEncryptionCertificatePath($configurations['encryption_certificate'] ?? '');
        $this->dump_requests = $configurations['dump_requests'] ?? false;
        parent::__construct($configurations['wsdl'], $configurations['options'] ?? ['trace' => true]);

        $this->config = $configurations;
    }

    private array $config;

    /**
     * @var boolean
     */
    private $dump_requests;

    /**
     * @var string
     */
    private $_username;

    /**
     * @var string
     */
    private $_password;

    /**
     * @var bool
     */
    private $_digest;

    /**
     * @var string
     */
    private static $private_key;

    /**
     * @var string
     */
    private static $cert_path;

    /**
     * @var string
     */
    private static $encryption_file;

    /**
     *
     * @var boolean
     */
    public $encryption_needed = false;

    /**
     *
     * @var boolean
     */
    public $signature_verification_needed = false;

    /**
     * @var array
     */
    private $configurations = [];

    /**
     * @var array
     */
    private array $payload;

    /**
     * @var string
     */
    public string $logger_name = 'pacg_adm';

    /**
     * @param $path
     * @return void
     */
    public static function setPrivateKey($path)
    {
        self::$private_key = $path;
    }

    /**
     * @param $path
     * @return void
     */
    public static function setSigningCertificatePath($path)
    {
        self::$cert_path = $path;
    }

    /**
     * @param $path
     * @return void
     */
    public static function setEncryptionCertificatePath($path)
    {
        self::$encryption_file = $path;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function getPrivateKey(): string
    {
        if (empty(self::$private_key)) {
            throw new \Exception("The private key path must be informed");
        }

        return self::$private_key;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function getCertFile(): string
    {
        if (empty(self::$cert_path)) {
            throw new \Exception("The certificate path must be informed");
        }

        $cert_file = file_get_contents(self::$cert_path);

        if (empty($cert_file)) {
            throw new \Exception("The certificate path is wrong or the file is empty");
        }

        return $cert_file;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function getEncryptionFile(): string
    {
        if (empty(self::$encryption_file)) {
            throw new \Exception("The encryption certificate path must be informed");
        }

        return self::$encryption_file;
    }

    /**
     * @return resource
     */
    private function getContextConfiguration()
    {
        return stream_context_create([
            'ssl' => [
                // set some SSL/TLS specific options
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
    }

    /**
     * @param $username
     * @param $password
     * @param bool $digest
     * @return void
     */
    public function setDataForUserToken($username, $password = null, $digest = false)
    {
        $this->_username = $username;
        $this->_password = $password;
        $this->_digest = $digest;
    }


    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     * @return bool|string
     * @throws \SoapFault
     * @throws \Exception
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $doc = new DOMDocument('1.0');
        $this->dumptst('pacg-req-no-crypt', ['request' => $request, 'location' => $location, 'action' => $action]);
        $doc->loadXML($request);
        $objWSSE = new WSSESoap($doc);
        /* Sign all headers to include signing the WS-Addressing headers */
        $objWSSE->signAllHeaders = true;
        $objWSSE->addTimestamp();

        $this->addUserToken($objWSSE);

        $this->addSignature($objWSSE);

        if($this->encryption_needed) {
            $this->encryptBody($objWSSE);
        }

        $request = $objWSSE->saveXML();
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        if ((isset($this->__soap_fault) && !empty($this->__soap_fault))) {
            $this->dumptst('pacg-soap-fault', ['exception' => $this->__soap_fault]);
            throw new \Exception($this->__soap_fault);
        }

        $this->dumptst('pacg-request-crypt', ['request' => $request, 'response' => $response]);

        $this->verifySignature($response);

        return $response;
    }

    /**
     * @param WSSESoap $objWSSE
     * @throws \Exception
     */
    private function addUserToken(WSSESoap $objWSSE)
    {
        $objWSSE->addUserToken($this->_username, $this->_password, $this->_digest);
    }

    /**
     * @param WSSESoap $objWSSE
     * @throws \Exception
     */
    private function addSignature(WSSESoap $objWSSE)
    {
        /* create new XMLSec Key using RSA SHA-1 and type is private key */
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
        /* load the private key from file - last arg is bool if key in file (true) or is string (FALSE) */
        $objKey->loadKey(self::getPrivateKey(), true);
        /* Sign the message - also signs appropraite WS-Security items */
        $objWSSE->signSoapDoc($objKey);
        /* Add certificate (BinarySecurityToken) to the message and attach pointer to Signature */
        $token = $objWSSE->addBinaryToken(self::getCertFile());
        $objWSSE->attachTokentoSig($token);
    }

    public function encryptBody($objWSSE)
    {
        $objKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
        $objKey->generateSessionKey();

        $encryption_certificate = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type' => 'public'));
        $encryption_certificate->loadKey(self::getEncryptionFile(), true, true);

        $options = array("KeyInfo" => array("X509SubjectKeyIdentifier" => true));
        $objWSSE->encryptSoapDoc($encryption_certificate, $objKey, $options, false);
    }

    /**
     * @param $response
     * @throws \Exception
     */
    private function verifySignature($response)
    {
        if (!empty($response)) {
            $xml_handle = new DOMDocument();
            $xml_handle->loadXML($response);
            $validateSignature = new WSSESoapServer($xml_handle);
            if (!$validateSignature->process()) {
                throw new Exception('Verify signature fail');
            }
        } else {
            throw new Exception((new PacgReturnCode())->getCodeDescription(0));
        }

    }

    /**
     * @inheritDoc
     */
    public function exec(array $payload)
    {
        $this->payload = $payload;
        return $this->__soapCall($this->configurations['functionName'], [$this->payload]);
    }

    /**
     * @inheritDoc
     */
    public function setConfigurations(array $configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * Helper function to log
     *
     * @param $tag
     * @param $content
     */
    public function dumpTst($tag, $content)
    {
        if ($this->dump_requests === true) {
            phive()->dumpTbl($tag, $content);
        }
    }

    /**
     * Return data used to compose the request
     * @return array
     */
    public function getPayloadRequest(): array
    {
        return [
            'headers' =>  $this->configurations,
            'body' =>  $this->payload,
        ];
    }

    public function getProtocolVersion(): string
    {
        return $this->config['protocol_version'] ?? self::DEFAULT_PROTOCOL_VERSION;
    }
}
