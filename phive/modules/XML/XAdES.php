<?php

namespace XML;

use DOMDocument;
use DOMElement;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class XAdES
{
    /**
     * @param DOMDocument $xml
     * @param string $private_key
     * @param string $certificate
     * @noinspection PhpUnhandledExceptionInspection ignoring invalid argument exceptions, since we are passing constants
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function sign(DOMDocument $xml, string $private_key, string $certificate)
    {
        //Initialize
        $objDSig = new XMLSecurityDSig();
        $signature_id = 'id-' . uniqid();
        $objDSig->sigNode->setAttribute('Id', $signature_id);

        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

        //load key and cert
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);

        $objKey->loadKey($private_key, true);

        $objDSig->add509Cert($certificate, true, true, ['subjectName' => true]);


        $creator = $objDSig->sigNode->ownerDocument;

        //Add xades block
        $qualifying_properties = $creator->createElement('xades:QualifyingProperties');
        $qualifying_properties->setAttributeNS(
            'http://www.w3.org/2000/xmlns/', 'xmlns:xades',
            'http://uri.etsi.org/01903/v1.3.2#'
        );
        $qualifying_properties->setAttribute('Target', "#{$signature_id}");

        $signed_properties = $creator->createElementNS('http://uri.etsi.org/01903/v1.3.2#', 'xades:SignedProperties');
        $signed_properties->setAttribute('Id', "xades-{$signature_id}");
        $qualifying_properties->appendChild($signed_properties);

        $signed_signature_properties = $creator->createElement('xades:SignedSignatureProperties');
        $signed_properties->appendChild($signed_signature_properties);

        $signed_signature_properties->appendChild(
            $creator->createElement('xades:SigningTime', date('c'))
        );

        $objDSig->addObject($qualifying_properties);


        //Add references (things to sign): main document, KeyInfo (as requested by xades), SignedProperties
        $objDSig->addReference(
            $xml,
            XMLSecurityDSig::SHA256,
            [
                'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                XMLSecurityDSig::EXC_C14N,
            ],
            ['force_uri' => true]
        );

        $objDSig->addReference(
            $objDSig->sigNode->getElementsByTagName('KeyInfo')[0],
            XMLSecurityDSig::SHA256,
            [XMLSecurityDSig::EXC_C14N]
        );

        /** @noinspection PhpParamsInspection The first parameter expects a DOMNode, but the phpDoc on the method is wrong */
        $objDSig->addReference(
            $signed_properties,
            XMLSecurityDSig::SHA256,
            [XMLSecurityDSig::EXC_C14N],
            ['overwrite' => false]
        );

        //The Reference to SignedProperties needs a Type attribute, but the library is missing the option
        //we search it by uri and apply the attribute
        /** @var DOMElement $reference */
        foreach ($objDSig->sigNode->getElementsByTagName('Reference') as $reference) {
            if ($reference->getAttribute('URI') === "#xades-{$signature_id}") {
                $reference->setAttribute('Type', 'http://uri.etsi.org/01903#SignedProperties');
                break;
            }
        }

        $objDSig->sign($objKey, $xml->documentElement);

    }
}