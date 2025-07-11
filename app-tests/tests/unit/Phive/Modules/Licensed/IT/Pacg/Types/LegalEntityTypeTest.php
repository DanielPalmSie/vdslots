<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\LegalEntityType;
use IT\Pacg\Types\ResidenceType;

/**
 * Class LegalEntityTypeTest
 */
class LegalEntityTypeTest extends AbstractTypeTest
{
    /**
     * @var LegalEntityType
     */
    protected $stub;

    /**
     * LegalEntityTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(LegalEntityType::class)->makePartial();
    }

    public function testSuccessSettingLegalType()
    {
        $payload = $this->getLegalPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('partitaIva', $return_to_array));
        $this->assertTrue(array_key_exists('ragioneSociale', $return_to_array));
        $this->assertTrue(array_key_exists('sede', $return_to_array));
        $this->assertTrue(array_key_exists('postaElettronica', $return_to_array));
        $this->assertTrue(array_key_exists('pseudonimo', $return_to_array));

        $this->assertEquals($payload['vat_number'], $return_to_array['partitaIva']);
        $this->assertEquals($payload['company_name'], $return_to_array['ragioneSociale']);
        $this->assertEquals($payload['email'], $return_to_array['postaElettronica']);
        $this->assertEquals($payload['pseudonym'], $return_to_array['pseudonimo']);
        $this->assertInstanceOf(ResidenceType::class, $this->stub->company_headquarter);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getLegalPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['vat_number']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(! array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['company_name']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['email']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['company_headquarter']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));


        unset($payload['pseudonym']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(array_key_exists('company_headquarter', $this->stub->errors));
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));
    }

    public function testLegalPayloadValidateFail()
    {
        $payload = $this->getLegalPayload();
        $payload['vat_number'] = '1234567890';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));

        $payload = $this->getLegalPayload();
        $payload['vat_number'] = '123456789012';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('vat_number', $this->stub->errors));

        $payload = $this->getLegalPayload();
        $payload['company_name'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['company_name'] .= '1234567890';
        }
        $payload['company_name'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('company_name', $this->stub->errors));

        $payload = $this->getLegalPayload();
        $payload['email'] = 'test';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('email', $this->stub->errors));

        $payload = $this->getLegalPayload();
        $payload['pseudonym'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['pseudonym'] .= '1234567890';
        }
        $payload['pseudonym'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));
    }

    public function testErrorToSetCompanyHeadquarterException()
    {
        $payload = $this->getLegalPayload();
        unset($payload['company_headquarter']['residential_address']);
        $this->expectException(\Exception::class);
        $this->stub->setCompanyHeadquarter($payload['company_headquarter']);
    }

    /**
     * @return array
     */
    private function getLegalPayload(): array
    {
        return [
            'vat_number' => '12345678901',
            'company_name' => 'test',
            'company_headquarter' => [
                'country' => ResidenceType::ITALY,
                'residential_address'          => 'Some street',
                'municipality_of_residence'    => 'Roma',
                'residential_province_acronym' => 'RM',
                'residential_post_code'        => '12345',
            ],
            'email' => 'test@videoslots.com',
            'pseudonym' => 'test',
        ];
    }
}