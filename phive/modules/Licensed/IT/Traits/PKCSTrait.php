<?php

namespace IT\Traits;

use Exception;
use Sop\ASN1\Element;
use Sop\ASN1\Type\Constructed\Sequence;
use Sop\ASN1\Type\Constructed\Set;
use Sop\ASN1\Type\Primitive\Integer;
use Sop\ASN1\Type\Primitive\NullType;
use Sop\ASN1\Type\Primitive\ObjectIdentifier;
use Sop\ASN1\Type\Primitive\OctetString;
use Sop\ASN1\Type\Primitive\PrintableString;
use Sop\ASN1\Type\Primitive\UTF8String;
use Sop\ASN1\Type\Tagged\ExplicitlyTaggedType;
use Sop\ASN1\Type\UnspecifiedType;

/**
 * Trait PKCSTrait
 * @package IT\Pgda\Services
 */
trait PKCSTrait
{
    /**
     * Signing and enveloping message
     *
     * @param $message
     * @return mixed
     */
    public static function envelope($message, $private_key, $certificate)
    {
        if (is_string($certificate)) {
            $certificate = openssl_x509_parse("file://{$certificate}", true);
        }

        if (is_string($private_key)) {
            $private_key = openssl_pkey_get_private("file://{$private_key}");
        }

        if (! openssl_sign($message, $signature, $private_key)) {
            throw new Exception('Unable to sign message');
        }

        $seq = new Sequence(
            new ObjectIdentifier('1.2.840.113549.1.7.2'),
            new ExplicitlyTaggedType(0, new Sequence(
                new Integer(1),
                new Set(
                    new Sequence(
                        new ObjectIdentifier('1.3.14.3.2.26'),
                    )
                ),
                new Sequence(
                    new ObjectIdentifier('1.2.840.113549.1.7.1'),
                    new ExplicitlyTaggedType(Element::TYPE_EOC, new OctetString($message))
                ),
                new Set(
                    new Sequence(
                        new Integer(1),
                        new Sequence(
                            new Sequence(
                                new Set(
                                    new Sequence(
                                        new ObjectIdentifier('2.5.4.6'),
                                        new PrintableString($certificate['issuer']['C'])
                                    )
                                ),
                                new Set(
                                    new Sequence(
                                        new ObjectIdentifier('2.5.4.10'),
                                        new UTF8String($certificate['issuer']['O'])
                                    )
                                ),
                                new Set(
                                    new Sequence(
                                        new ObjectIdentifier('2.5.4.3'),
                                        new UTF8String($certificate['issuer']['CN'])
                                    )
                                )
                            ),
                            new Integer($certificate['serialNumber'])
                        ),
                        new Sequence(
                            new ObjectIdentifier('1.3.14.3.2.26')
                        ),
                        new Sequence(
                            new ObjectIdentifier('1.2.840.113549.1.1.1'),
                            new NullType()
                        ),
                        new OctetString($signature)
                    )
                )
            ))
        );

        openssl_free_key($private_key);

        return $seq->toDER();
    }

    /**
     * @param $der
     * @return string
     * @throws Exception
     */
    protected static function reveal($der)
    {
        // In most cases it can be string "Error 202: SRVE0295E: Error reported: 202"
        if (! static::isBinary($der)) {
            return $der;
        }

        try {
            $seq = UnspecifiedType::fromDER($der)->asSequence();
            $pkcs = $seq->at(1)->asTagged()->asExplicit()->asSequence();
            $data = $pkcs->at(2)->asSequence();

            // check if type is Octet String (this started happening after PGDA_BaseUrl was changed)
            if($data->at(1)->asTagged()->asExplicit()->isType($data::TYPE_OCTET_STRING))
                return $data->at(1)->asTagged()->asExplicit()->asOctetString()->string();

            return $data->at(1)->asTagged()->asExplicit()->asConstructedString()->string();
        } catch(Exception $e) {
            throw $e;
        }
    }

    /**
     * Determine whether the given value is a binary string by checking to see if it contains only printable characters.
     * @link https://www.php.net/manual/ru/function.ctype-print.php
     * @param string $value
     *
     * @return bool
     */
    protected static function isBinary($value): bool
    {
        // remove "unprintable" whitespace characters (tabs, newlines etc)
        $string = preg_replace('/\s/', '', (string) $value);

        return ! empty($string) && ! ctype_print($string);
    }
}
