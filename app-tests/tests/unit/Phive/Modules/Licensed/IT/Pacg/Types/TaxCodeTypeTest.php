<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\TaxCodeType;

/**
 * Class TaxCodeTypeTest
 */
class TaxCodeTypeTest extends AbstractTypeTest
{
    /**
     * @var TaxCodeType
     */
    protected $stub;

    /**
     * TaxCodeTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(TaxCodeType::class)->makePartial();
    }

    public function testSuccessSettingTaxCodeType()
    {
        $payload = $this->getTaxCodePayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('name', $return_to_array));
        $this->assertTrue(array_key_exists('surname', $return_to_array));
        $this->assertTrue(array_key_exists('birthDate', $return_to_array));
        $this->assertTrue(array_key_exists('gender', $return_to_array));
        $this->assertTrue(array_key_exists('registryCode', $return_to_array));

        $this->assertEquals($payload['name'], $return_to_array['name']);
        $this->assertEquals($payload['surname'], $return_to_array['surname']);
        $this->assertEquals($payload['birthDate'], $return_to_array['birthDate']);
        $this->assertEquals($payload['gender'], $return_to_array['gender']);
        $this->assertEquals($payload['registryCode'], $return_to_array['registryCode']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getTaxCodePayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('registryCode', $this->stub->errors));

        unset($payload['name']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('registryCode', $this->stub->errors));

        unset($payload['surname']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('registryCode', $this->stub->errors));

        unset($payload['birthDate']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('registryCode', $this->stub->errors));

        unset($payload['gender']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('registryCode', $this->stub->errors));

        unset($payload['registryCode']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('registryCode', $this->stub->errors));
    }

    public function testTaxCodePayloadValidateFail()
    {
        $payload = $this->getTaxCodePayload();
        $payload['name'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['name'] .= '1234567890';
        }
        $payload['name'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));

        $payload = $this->getTaxCodePayload();
        $payload['surname'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['surname'] .= '1234567890';
        }
        $payload['surname'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));

        $payload = $this->getTaxCodePayload();
        $payload['birthDate'] = '2020-20-2';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));

        $payload = $this->getTaxCodePayload();
        $payload['birthDate'] = '2020-20-002';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('birthDate', $this->stub->errors));

        $payload = $this->getTaxCodePayload();
        $payload['gender'] = 'W';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));

        $payload = $this->getTaxCodePayload();
        $payload['registryCode'] .= '1';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('registryCode', $this->stub->errors));

        $payload['registryCode'] = '1';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('registryCode', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getTaxCodePayload(): array
    {
        return  [
            "name" => 'First Name',
            "surname" => 'Last Name',
            "birthDate" => '1985-09-17',
            "gender" => 'M',
            "registryCode" => 'A234'
        ];
    }
}