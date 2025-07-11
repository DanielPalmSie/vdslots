<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$legal_entity_hq = new \IT\Pacg\Types\ResidenceType();
$legal_entity_hq->setResidentialAddress('Some street');
$legal_entity_hq->setMunicipalityOfResidence("Rome");
$legal_entity_hq->setResidentialPostCode('12345');  // need to be exactly 5 characters
$legal_entity_hq->setResidentialProvinceAcronym('RO');  // need to be exactly 2 characters

$legal_entity = new \IT\Pacg\Types\LegalEntityType();
$legal_entity->setCompanyName('Videoslots');
$legal_entity->setEmail('info@videoslots.com');
$legal_entity->setVatNumber('12345678901');  // need to be exactly 5 characters
$legal_entity->setPseudonym('PandaMedia');
$legal_entity->setCompanyHeadquarter($legal_entity_hq);

$open_account = new \IT\Pacg\Services\OpenAccountLegalEntity();
$open_account->setAccountHolder($legal_entity);
$open_account->setAccountCode('accountcode');


$settings = $licenced_module->getSetting('IT')['pacg'];

\IT\Pacg\Client\Client::setPrivateKey($settings['private_key']);
\IT\Pacg\Client\Client::setCertFile($settings['signing_certificate']);

$sClient = new \IT\Pacg\Client\Client($settings['wsdl']);

$openAccountLegalRequest = new \IT\Pacg\Requests\OpenAccountLegalRequest($sClient, $settings['wss'], $settings['id_fsc'], $settings['id_cn']);

try {
    $response = $openAccountLegalRequest->request($open_account);
    echo 'success: ';
    print_r($response);
} catch (Exception $exception) {
    echo 'Exception: ';
    print_r($exception);
} catch (SoapFault $soapfault) {  // We need to catch a possible SoapFault too (but not working??)
    echo 'SoapFault: ';
    print_r($exception);
}
