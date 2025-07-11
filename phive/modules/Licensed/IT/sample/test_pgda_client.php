<?php
declare(strict_types=1);

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;
use GuzzleHttp\Exception\GuzzleException as GuzzleExceptionAlias;
use IT\Pgda\Client\PgdaClient;

use Sop\ASN1\DERData;
use Sop\ASN1\Element;
use Sop\ASN1\Type\Constructed\ConstructedString;
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

// @todo get paths from config
$certificate_path = realpath(__DIR__ . '/modules/Licensed/IT/certificates/signing.crt');
$key_path = realpath(__DIR__ . '/modules/Licensed/IT/certificates/private_key_no.pem');

$config = phive('Licensed')->getSetting('IT');
$pgda_headers = [
    'Cod_vers_prot' => $config['pgda']['protocol_version'],
    'Cod_fsc' => $config['id_fsc'],
    'Cod_conc_trasmittente' => $config['id_cn'],
    'Cod_conc_proponente' => $config['id_cn'],
    'Cod_gioco' => 0,
    'Cod_tipo_gioco' => '2',
    'Cod_tipo_messaggio' => '831',
    'Id_transazione' => time(),
];
$data = [
    'cod_tipo_elemento' => 1,
    'cod_elemento' => 831,
    'prog_versione_cert' => 1,
    'prog_sub_versione_cert' => 2,
    'ctr_lista_moduli_sw' => 1,
    'module_list' => [
        'lun_nome_modulo_critico' => 19,
        'nome_modulo_critico' => 'nginx-1.18.0.tar.gz',
        'hash_code_modulo_critico' => '47b2c5ccd12e2a7088b03d629ff6b9ab18215180'
    ]
];

$body = http_build_query(
    $data,
    '',
    '&amp;',
    PHP_QUERY_RFC1738
);

$pkcs7_oid = new ObjectIdentifier('1.2.840.113549.1.7.2');
$encryption_algorithm = new Sequence(
    new ObjectIdentifier('1.3.14.3.2.26'),
    new NullType()
);

$encryption_algorithm_set = new Set($encryption_algorithm);
$data_octet_string = new OctetString("$body\n");
$constructed_octet_string = ConstructedString::createWithTag(
    Element::TYPE_OCTET_STRING,
    $data_octet_string
);
$ett_octet_string_data = new ExplicitlyTaggedType(0, $constructed_octet_string->withIndefiniteLength());
$sqc_ett_osd = new Sequence(
    new ObjectIdentifier('1.2.840.113549.1.7.1'),
    $ett_octet_string_data->asElement()->withIndefiniteLength()
);

$certificate = file_get_contents($certificate_path);
$parsed_certificate = openssl_x509_parse($certificate, false);

$seq_three = new Sequence(
    new Sequence(
    // http://oid-info.com/get/2.5.4.6
        new Set(
            new Sequence(
                new ObjectIdentifier('2.5.4.6'),
                new PrintableString($parsed_certificate['issuer']['countryName'])
            )
        ),
        // http://oid-info.com/get/2.5.4.10
        new Set(
            new Sequence(
                new ObjectIdentifier(' 2.5.4.10'),
                new UTF8String($parsed_certificate['issuer']['organizationName'])
            )
        ),
        // http://oid-info.com/get/2.5.4.11
        new Set(
            new Sequence(
                new ObjectIdentifier(' 2.5.4.11'),
                new UTF8String($parsed_certificate['subject']['organizationalUnitName'])
            )
        ),
        // http://oid-info.com/get/2.5.4.3
        new Set(
            new Sequence(
                new ObjectIdentifier('2.5.4.3'),
                new UTF8String($parsed_certificate['issuer']['commonName'])
            )
        )
    ),
    new Integer($parsed_certificate['serialNumber'])
);
$content_type = new Sequence(
    new ObjectIdentifier('1.2.840.113549.1.9.3'),
    new Set(new ObjectIdentifier('1.2.840.113549.1.7.1'))
);
$content_type->withIndefiniteLength();
$signing_date = new DateTime('now', new DateTimeZone('UTC'));
$signing_time = new Sequence(
    new ObjectIdentifier('1.2.840.113549.1.9.5'),
    new Set(new UTCTime(new DateTimeImmutable($signing_date->format('Y-m-d H:i e'))))
);
$signing_time->withIndefiniteLength();
$message_digest = new Sequence(
    new ObjectIdentifier('1.2.840.113549.1.9.4'),
    new Set(new OctetString(openssl_digest("$body\n", 'sha1')))
);
$message_digest->withIndefiniteLength();

$signature = '';
$private_key = openssl_pkey_get_private("file://$key_path");
$res = openssl_sign("$body\n", $signature, $private_key);

$seq_two = new Sequence(
    new Integer(1),
    $seq_three,
    new Sequence(
        new ObjectIdentifier('1.3.14.3.2.26'),
        new NullType()
    ),
    new ExplicitlyTaggedType(0,
        new DERData($content_type->toDER() . $signing_time->toDER() . $message_digest->toDER())
    ),
    new Sequence(
        new ObjectIdentifier(' 1.2.840.113549.1.1.1'),
        new NullType()
    ),
    new OctetString($signature)
);

$set_two = new Set(
    $seq_two
);

$seq_one = new Sequence(
    new Integer(1),
    $encryption_algorithm_set,
    $sqc_ett_osd->asElement()->withIndefiniteLength(),
    $set_two // @todo wrong length so far
);

$el_one = new ExplicitlyTaggedType(
    0,
    $seq_one->asElement()->withIndefiniteLength()
);

$seq = new Sequence(
    $pkcs7_oid,
    $el_one->asElement()->withIndefiniteLength()
);
$body_signed = $seq->asElement()->withIndefiniteLength()->toDER();
printf("%s\n", bin2hex($body_signed));
$url = "http://10.70.78.131/GiochiDiAbilitaV2_1/ServletFactoryFirma_V213_SH";

$stream = new GuzzleHttp\Psr7\MultipartStream([
    ['name' => 'body', 'contents' => $body, 'headers' => ["Content-Type" => 'application/x-www-form-urlencoded']],
    [
        'name' => 'signature',
        'contents' => bin2hex($body_signed),
        'headers' => ["Content-Type" => 'multipart/signed; protocol="application/pkcs7-signature"; micalg=sha1']],
]);
$request = new Request('POST', $url, $pgda_headers, $stream);
$stack = HandlerStack::create();
$stack->push(
    Middleware::log(
        new Logger('Logger'),
        new MessageFormatter('{request} - {response}')
    )
);
$client = new PgdaClient(['url' => 'http://10.70.78.131', 'handler' => $stack]);
try {
    $response = $client->send($request);
} catch (GuzzleExceptionAlias $e) {
    echo $e->getMessage() . PHP_EOL;
    print_r($e->getTraceAsString() . PHP_EOL);
}
print_r($response->getBody()->getContents() . PHP_EOL);
