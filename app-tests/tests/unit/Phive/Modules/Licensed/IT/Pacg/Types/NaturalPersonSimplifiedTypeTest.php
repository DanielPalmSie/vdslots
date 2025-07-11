<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\BirthDataType;
use IT\Pacg\Types\NaturalPersonSimplifiedType;

/**
 * Class NaturalPersonSimplifiedTypeTest
 */
class NaturalPersonSimplifiedTypeTest extends AbstractTypeTest
{
    /**
     * @var NaturalPersonSimplifiedType
     */
    protected $stub;

    /**
     * NaturalPersonSimplifiedTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(NaturalPersonSimplifiedType::class)->makePartial();
    }

    public function testSuccessSettingNaturalPersonSimplifiedType()
    {
        $payload = $this->getNaturalPersonSimplifiedPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceFiscale', $return_to_array));
        $this->assertTrue(array_key_exists('cognome', $return_to_array));
        $this->assertTrue(array_key_exists('nome', $return_to_array));
        $this->assertTrue(array_key_exists('sesso', $return_to_array));
        $this->assertTrue(array_key_exists('nascita', $return_to_array));
        $this->assertTrue(array_key_exists('provResid', $return_to_array));

        $this->assertEquals($payload['tax_code'], $return_to_array['codiceFiscale']);
        $this->assertEquals($payload['surname'], $return_to_array['cognome']);
        $this->assertEquals($payload['name'], $return_to_array['nome']);
        $this->assertEquals($payload['gender'], $return_to_array['sesso']);
        $this->assertEquals($payload['residential_province_acronym'], $return_to_array['provResid']);

        $this->assertInstanceOf(BirthDataType::class, $this->stub->birth_data);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getNaturalPersonSimplifiedPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(6, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['tax_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['surname']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['name']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['gender']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['birth_data']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));

        unset($payload['residential_province_acronym']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);

        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));
    }

    public function testNaturalPersonSimplifiedPayloadValidateFail()
    {
        $payload = $this->getNaturalPersonSimplifiedPayload();
        $payload['tax_code'] = 'MRCFNZ85P17F205';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));

        $payload = $this->getNaturalPersonSimplifiedPayload();
        $payload['surname'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['surname'] .= '1234567890';
        }
        $payload['surname'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));

        $payload = $this->getNaturalPersonSimplifiedPayload();
        $payload['name'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['name'] .= '1234567890';
        }
        $payload['name'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));

        $payload = $this->getNaturalPersonSimplifiedPayload();
        $payload['gender'] = 'W';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));

        $payload = $this->getNaturalPersonSimplifiedPayload();
        $payload['residential_province_acronym'] = 'R';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));

        $payload['residential_province_acronym'] = 'RMM';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));
    }

    public function testErrorToSetDateOfBirthException()
    {
        $payload = $this->getNaturalPersonSimplifiedPayload();
        unset($payload['birth_data']['country']);
        $this->expectException(\Exception::class);
        $this->stub->setDateOfBirth($payload['birth_data']);
    }

    /**
     * @return array
     */
    private function getNaturalPersonSimplifiedPayload(): array
    {
        return [
            'tax_code' => 'MRCFNZ85P17F205C',
            'surname' => 'ARNALDI',
            'name' => 'test',
            'gender' => 'M',
            'birth_data' => [
                'country' => BirthDataType::ITALY,
                'date_of_birth' => [
                    'day'   => '15',
                    'month' => '10',
                    'year'  => '1965',
                ],
                'birthplace'                  => 'Roma',
                'birthplace_province_acronym' => 'RM',
            ],
            'residential_province_acronym'  => 'RM',
        ];
    }
}