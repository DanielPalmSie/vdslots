<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\BirthDataType;
use IT\Pacg\Types\DateType;

/**
 * Class BirthDataTypeTest
 */
class BirthDataTypeTest extends AbstractTypeTest
{
    /**
     * @var BirthDataType
     */
    protected $stub;

    /**
     * BirthDataTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(BirthDataType::class)->makePartial();
    }

    public function testSetDateOfBirthDateTypeReturn()
    {
        $payload = $this->getBirthDataPayload();
        $this->stub->fill($payload);
        $this->assertInstanceOf(DateType::class, $this->stub->date_of_birth);
    }

    public function testSuccessSettingBirthDataType()
    {
        $payload = $this->getBirthDataPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));
        $this->assertTrue(array_key_exists('data', $return_to_array));
        $this->assertTrue(array_key_exists('giorno', $return_to_array['data']));
        $this->assertTrue(array_key_exists('mese', $return_to_array['data']));
        $this->assertTrue(array_key_exists('anno', $return_to_array['data']));
        $this->assertTrue(array_key_exists('comune', $return_to_array));
        $this->assertTrue(array_key_exists('provincia', $return_to_array));

        $this->assertEquals($payload['date_of_birth']['day'], $return_to_array['data']['giorno']);
        $this->assertEquals($payload['date_of_birth']['month'], $return_to_array['data']['mese']);
        $this->assertEquals($payload['date_of_birth']['year'], $return_to_array['data']['anno']);
        $this->assertEquals($payload['birthplace'], $return_to_array['comune']);
        $this->assertEquals($payload['birthplace_province_acronym'], $return_to_array['provincia']);
    }

    public function testBirthInItaly()
    {
        $payload = $this->getBirthDataPayload(BirthDataType::ITALY);
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertEquals($payload['birthplace'], $return_to_array['comune']);
        $this->assertEquals($payload['birthplace_province_acronym'], $return_to_array['provincia']);
    }

    public function testDidntBirthInItaly()
    {
        $payload = $this->getBirthDataPayload('test');
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertEquals(BirthDataType::CITY_NO_RESIDENTS, $return_to_array['comune']);
        $this->assertEquals(BirthDataType::PROVINCE_NO_RESIDENTS, $return_to_array['provincia']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getBirthDataPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_birth', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthplace', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthplace_province_acronym', $this->stub->errors));

        unset($payload['country']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_of_birth', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthplace', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthplace_province_acronym', $this->stub->errors));

        unset($payload['date_of_birth']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_birth', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthplace', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthplace_province_acronym', $this->stub->errors));

        unset($payload['birthplace']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_birth', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthplace', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birthplace_province_acronym', $this->stub->errors));

        unset($payload['birthplace_province_acronym']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_birth', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthplace', $this->stub->errors));
        $this->assertTrue(array_key_exists('birthplace_province_acronym', $this->stub->errors));
    }

    /**
     * @param string $country
     * @return array
     */
    private function getBirthDataPayload(string $country = BirthDataType::ITALY): array
    {
        return [
            'country' => $country,
            'date_of_birth' => [
                'day'   => '15',
                'month' => '10',
                'year'  => '1965',
            ],
            'birthplace'                  => 'Roma',
            'birthplace_province_acronym' => 'RM',
        ];
    }
}