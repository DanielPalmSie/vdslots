<?php

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Psr7\Request;
use IT\Pgda\Codes\ReturnCode;
use Monolog\Logger;
use GuzzleHttp\Exception\GuzzleException as GuzzleExceptionAlias;
use IT\Pgda\Client\PgdaClient;

use Sop\ASN1\DERData;
use Sop\ASN1\Element;
use Sop\ASN1\Type\Constructed\Sequence;
use Sop\ASN1\Type\Constructed\Set;
use Sop\ASN1\Type\Primitive\Integer;
use Sop\ASN1\Type\Primitive\NullType;
use Sop\ASN1\Type\Primitive\ObjectIdentifier;
use Sop\ASN1\Type\Primitive\PrintableString;
use Sop\ASN1\Type\Primitive\UTCTime;
use Sop\ASN1\Type\Primitive\UTF8String;
use Sop\ASN1\Type\Primitive\OctetString;
use Sop\ASN1\Type\Tagged\ExplicitlyTaggedType;

//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

$config = phive('Licensed')->getSetting('IT');

/**
 * @param int $number
 * @param int $length
 * @return string
 */
$paddedInteger = function (int $number, int $length): string {
    return sprintf("%0" . $length * 2 . "s", dechex($number));
};

/**
 * @param string $string
 * @param int $length
 * @return string
 */
$paddedString = function (string $string, int $length): string {
    return str_pad(bin2hex($string), $length * 2, "20", STR_PAD_RIGHT);
};

/**
 * @return string
 */
$getBody = function () use ($paddedInteger, $paddedString, $config): string {
    return implode('', [
        'cod_tipo_elemento' => $paddedInteger($config['pgda']['protocol_version'], 1),
        'cod_elemento' => $paddedInteger(45014, 4),
        'prog_versione_cert' => $paddedInteger(1, 1),
        'prog_sub_versione_cert' => $paddedInteger(0, 1),
        'ctr_lista_moduli_sw' => $paddedInteger(1, 2),
        'lun_nome_modulo_critico' => $paddedInteger(strlen(bin2hex('Modulo3')) / 2, 1),
        'nome_modulo_critico' => bin2hex('Modulo3'),
        'hash_code_modulo_critico' => $paddedString('3333333333333333333333333333333333333333', 40)
    ]);
};

/**
 * @param string $body
 * @param string $id
 * @return string
 */
$getHeaders = function (string $body, string $id) use ($paddedInteger, $paddedString, $config): string {
    return implode('', [
        'Cod_vers_prot' => $paddedInteger($config['pgda']['protocol_version'] ?? 2, 1),
        'Cod_fsc' => $paddedInteger($config['id_fsc'], 4),
        'Cod_conc_trasmittente' => $paddedInteger($config['id_cn'], 4),
        'Cod_conc_proponente' => $paddedInteger($config['id_cn'], 4),
        'Cod_gioco' => $paddedInteger(0, 4),
        'Cod_tipo_gioco' => $paddedInteger(0, 1),
        'Cod_tipo_messaggio' => $paddedString('831', 4),
        'Id_transazione' => $paddedString($id, 16),
        'Lun_body' => $paddedInteger(strlen($body) / 2, 4)
    ]);
};

/**
 * @param string $headers
 * @param string $body
 * @return string
 */
$getMessage = function (string $headers, string $body): string {
    return pack("H*", "{$headers}{$body}");
};

/**
 * Utility function
 *
 * Use to debug body and headers payload
 * @param string $config_string
 */
$printPlainText = function (string $config_string): void {
    $config = explode('', $config_string);
    var_dump(array_walk($config, function ($value, $key) {
        echo strlen($value) / 2 . " - " . $value . PHP_EOL;
    }));
};

/**
 * @param $message string - row binary data
 * @param $transaction_id string
 */
$saveMessageToFile = function (string $message, string $transaction_id): void {
    echo "message: $message" . PHP_EOL;
    echo "bites written: ";
    echo file_put_contents("message-831-${transaction_id}.dat", $message);
    echo PHP_EOL;
};

/**
 * Send message to the desired API endpoint
 * @param string $message
 */
$sendMessage = function (string $message) use ($config): void {
    $url = (true === $config['pgda']['sign_message']) ? $config['pgda']['url'] : $config['pgda']['url_plain'];
    $req_headers["Content-Type"] = 'text/plain';
    $request = new Request('POST', $url, $req_headers, $message);
    $stack = HandlerStack::create();
    $stack->push(
        Middleware::log(
            new Logger('Logger'),
            new MessageFormatter('{request} - {response}')
        )
    );

    $client = new PgdaClient(['url' => $url, 'handler' => $stack]);

    try {
        $response = $client->send($request);
        echo 'RESPONSE' . PHP_EOL;
        $response_body = bin2hex($response->getBody());
        $error_code = hexdec(substr($response_body, -4));
        print_r($response_body . PHP_EOL);
        print_r($error_code . PHP_EOL);
        if ($error_code !== 0) {
            $code = new ReturnCode();
            echo "ERROR CODE: " . $code->getCodeDescription($error_code) . PHP_EOL;
        }
    } catch (GuzzleExceptionAlias $e) {
        echo 'PROBLEM' . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
        print_r($e->getTraceAsString() . PHP_EOL);
    }
};

/**
 * @param string $message
 * @return string
 * @todo after testing handle $body
 */
$getAsn1EncodedMessage = function (string $message) use ($config, & $body): string {

    /**
     * @param string $path
     * @return array
     */
    $getCertificate = function (string $path): array {
        $certificate = file_get_contents($path);
        return openssl_x509_parse($certificate, false);
    };

    $certificate = $getCertificate($config['pgda']['signing_certificate']);

    /**
     * Secure Hash Algorithm, revision 1 (SHA-1)
     * @link http://oid-info.com/get/1.3.14.3.2.26
     *
     * RSAES-PKCS1-v1_5 encryption scheme
     * @link http://oid-info.com/get/1.2.840.113549.1.1.1
     *
     * @param $message
     * @return Set
     */
    $getSignatureSet = function ($message) use ($certificate, $config) {

        /**
         * Computes a digest
         *
         * @param string $data
         * @return string
         */
        $getDigest = function (string $data) {
            $digest = openssl_digest($data, 'SHA1', true);

            if (false === $digest) {
                throw new RuntimeException('Failed to create data digest', 100100);
            }

            return $digest;
        };

        /**
         * Generates signature
         *
         * @param string $data
         * @return string
         */
        $getSignature = function (string $data) use ($getDigest, $config): string {
            $signed_data = '';
            $private_key = openssl_pkey_get_private("file://" . $config['pgda']['private_key']);
            $digest = $getDigest($data);

            $res = openssl_sign($digest, $signed_data, $private_key);

            if (false === $res) {
                throw new RuntimeException('Failed to sign data.');
            }

            return $signed_data;
        };

        /**
         * Certificate
         * @link http://oid-info.com/get/2.5.4.6 (Country Name)
         * @link http://oid-info.com/get/2.5.4.10 (Organization Name)
         * @link http://oid-info.com/get/2.5.4.11 (Oranization Unit Name)
         * @link http://oid-info.com/get/2.5.4.3 (Common Name based on asn.1 documentation. ADM exaqmple is duplicating Oranization Unit Name)
         *
         * @return Sequence
         */
        $getCertificateSequence = function () use ($certificate) {
            return new Sequence(
                new Sequence(
                    new Set(
                        new Sequence(
                            new ObjectIdentifier('2.5.4.6'),
                            new PrintableString($certificate['issuer']['countryName'])
                        )
                    ),
                    new Set(
                        new Sequence(
                            new ObjectIdentifier(' 2.5.4.10'),
                            new UTF8String($certificate['issuer']['organizationName'])
                        )
                    ),
                    new Set(
                        new Sequence(
                            new ObjectIdentifier(' 2.5.4.11'),
                            new UTF8String($certificate['subject']['organizationalUnitName'])
                        )
                    ),
                    new Set(
                        new Sequence(
                            new ObjectIdentifier('2.5.4.11'),
//                    new ObjectIdentifier('2.5.4.3'),
                            new UTF8String($certificate['subject']['organizationalUnitName'])
//                    new UTF8String('91405930370-01')
                        )
                    )
                ),
                new Integer($certificate['serialNumber'])
            );
        };

        /**
         * id-contentType
         * @link http://oid-info.com/get/1.2.840.113549.1.9.3
         *
         * @return string
         */
        $getContentTypeDer = function () {
            $content_type = new Sequence(
                new ObjectIdentifier('1.2.840.113549.1.9.3'),
                new Set(new ObjectIdentifier('1.2.840.113549.1.7.1'))
            );

            return $content_type->withIndefiniteLength()->toDER();
        };

        /**
         * id-signingTime
         * @link http://oid-info.com/get/1.2.840.113549.1.9.5
         *
         * @return string
         * @throws Exception
         */
        $getSigningTimeDer = function () {
            $signing_date = new DateTime('now', new DateTimeZone('UTC'));
            $signing_time = new Sequence(
                new ObjectIdentifier('1.2.840.113549.1.9.5'),
                new Set(new UTCTime(new DateTimeImmutable($signing_date->format('Y-m-d H:i e'))))
            );

            return $signing_time->withIndefiniteLength()->toDER();
        };

        /**
         * id-messageDigest
         * @link http://oid-info.com/get/1.2.840.113549.1.9.4
         *
         * @param $digest
         * @return string
         */
        $getDigestSequenceDer = function ($digest) {
            $message_digest = new Sequence(
                new ObjectIdentifier('1.2.840.113549.1.9.4'),
                new Set(new OctetString($digest))
            );

            return $message_digest->withIndefiniteLength()->toDER();
        };

        /**
         * Secure Hash Algorithm, revision 1 (SHA-1)
         * @link http://oid-info.com/get/1.3.14.3.2.26
         *
         * RSAES-PKCS1-v1_5 encryption scheme
         * @link http://oid-info.com/get/1.2.840.113549.1.1.1
         */
        return new Set(
            new Sequence(
                new Integer(1),
                $getCertificateSequence(),
                new Sequence(
                    new ObjectIdentifier('1.3.14.3.2.26'),
                    new NullType()
                ),
                new ExplicitlyTaggedType(0,
                    new DERData($getContentTypeDer() . $getSigningTimeDer() . $getDigestSequenceDer($getDigest($message)))
                ),
                new Sequence(
                    new ObjectIdentifier('1.2.840.113549.1.1.1'),
                    new NullType()
                ),
                new OctetString($getSignature($message))
            )
        );
    };

    /**
     * id-data
     * @link http://oid-info.com/get/1.2.840.113549.1.7.1
     *
     * @param $message
     * @return Element|Sequence
     */
    $getDataSequence = function ($message) {
        $data_sequence = new Sequence(
            new ObjectIdentifier('1.2.840.113549.1.7.1'),
            new ExplicitlyTaggedType(Element::TYPE_EOC, new OctetString($message))
        );
        return $data_sequence->asElement()->withIndefiniteLength();
    };

    /**
     * @param $message
     * @return Element
     */
    $getWrappedMessageSequence = function ($message) use ($getDataSequence, $getSignatureSet) {
        /**
         * Secure Hash Algorithm, revision 1 (SHA-1)
         * @link http://oid-info.com/get/1.3.14.3.2.26
         */
        $wrapped_message_sequence = new Sequence(
            new Integer(1),
            new Set(new Sequence(
                new ObjectIdentifier('1.3.14.3.2.26'),
                new NullType()
            )),
            $getDataSequence($message),
            $getSignatureSet($message)
        );

        $wrapped = new ExplicitlyTaggedType(
            0,
            $wrapped_message_sequence->asElement()->withIndefiniteLength()
        );

        return $wrapped->asElement()->withIndefiniteLength();
    };

    /**
     * Rivest, Shamir and Adleman (RSA) applied over the PKCS#7 ASN.1 SignedData type
     * @link http://oid-info.com/get/1.2.840.113549.1.7.2
     */
    $seq = new Sequence(
        new ObjectIdentifier('1.2.840.113549.1.7.2'),
        $getWrappedMessageSequence($message)
    );

    return $seq->toDER();
};

$body = $getBody();
echo "Body in HEX: ${body}" . PHP_EOL;

$headers = $getHeaders($body, $transaction_id = (string)time());
echo "Headers in HEX: ${headers}" . PHP_EOL;

$message = $getMessage($headers, $body);
$saveMessageToFile($message, $transaction_id);
echo "Message in HEX: " . bin2hex($message) . PHP_EOL;

if (true === $config['pgda']['sign_message']) {
    $signed = $getAsn1EncodedMessage($message);
    echo bin2hex($signed) . PHP_EOL;
//$signed = file_get_contents('msg831.dat');
    $saveMessageToFile($signed, "tid-${transaction_id}");
    $sendMessage($signed);
} else {
    $sendMessage($message);
}
