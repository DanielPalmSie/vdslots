<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\ResidenceType;

/**
 * Class ResidenceTypeTest
 */
class ResidenceTypeTest extends AbstractTypeTest
{
    /**
     * @var ResidenceType
     */
    protected $stub;

    /**
     * ResidenceTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(ResidenceType::class)->makePartial();
    }

    public function testSetResidenceTypeReturn()
    {
        $payload = $this->getResidencePayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('indirizzo', $return_to_array));
        $this->assertTrue(array_key_exists('comune', $return_to_array));
        $this->assertTrue(array_key_exists('provincia', $return_to_array));
        $this->assertTrue(array_key_exists('cap', $return_to_array));

        $this->assertEquals($payload['residential_address'], $return_to_array['indirizzo']);
        $this->assertEquals($payload['municipality_of_residence'], $return_to_array['comune']);
        $this->assertEquals($payload['residential_province_acronym'], $return_to_array['provincia']);
        $this->assertEquals($payload['residential_post_code'], $return_to_array['cap']);
    }

    public function testResidenceInItaly()
    {
        $payload = $this->getResidencePayload(ResidenceType::ITALY);
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertEquals($payload['municipality_of_residence'], $return_to_array['comune']);
        $this->assertEquals($payload['residential_province_acronym'], $return_to_array['provincia']);
    }

    public function testDidntResidenceInItaly()
    {
        $payload = $this->getResidencePayload('test');
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertEquals(ResidenceType::CITY_NO_RESIDENTS, $return_to_array['comune']);
        $this->assertEquals(ResidenceType::PROVINCE_NO_RESIDENTS, $return_to_array['provincia']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getResidencePayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_post_code', $this->stub->errors));

        unset($payload['country']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(! array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_post_code', $this->stub->errors));

        unset($payload['residential_address']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(! array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_post_code', $this->stub->errors));

        unset($payload['municipality_of_residence']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_post_code', $this->stub->errors));

        unset($payload['residential_province_acronym']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residential_post_code', $this->stub->errors));

        unset($payload['residential_post_code']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('country', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));
        $this->assertTrue(array_key_exists('municipality_of_residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_province_acronym', $this->stub->errors));
        $this->assertTrue(array_key_exists('residential_post_code', $this->stub->errors));
    }

    public function testResidencePayloadValidateFail()
    {
        $payload = $this->getResidencePayload();
        $payload['residential_address'] = '';
        for ($i = 0; $i < 25; $i++ ) {
            $payload['residential_address'] .= '1234567890';
        }
        $payload['residential_address'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('residential_address', $this->stub->errors));

        $payload = $this->getResidencePayload();
        $payload['municipality_of_residence'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['municipality_of_residence'] .= '1234567890';
        }
        $payload['municipality_of_residence'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('municipality_of_residence', $this->stub->errors));

        $payload = $this->getResidencePayload();
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

        $payload = $this->getResidencePayload();
        $payload['residential_post_code'] .= '1';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('residential_post_code', $this->stub->errors));

        $payload['residential_post_code'] = '1';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('residential_post_code', $this->stub->errors));
    }

    /**
     * @param string $country
     * @return array
     */
    private function getResidencePayload(string $country = ResidenceType::ITALY): array
    {
        return [
            'country' => $country,
            'residential_address' => 'Some street',
            'municipality_of_residence' => 'Roma',
            'residential_province_acronym' => 'RM',
            'residential_post_code' => '12345',
        ];
    }
}