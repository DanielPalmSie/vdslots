<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\DateType;

/**
 * Class DateTypeTest
 */
class DateTypeTest extends AbstractTypeTest
{
    /**
     * @var DateType
     */
    protected $stub;

    /**
     * DateTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(DateType::class)->makePartial();
    }

    public function testSuccessSettingDateType()
    {
        $payload = $this->getDatePayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('giorno', $return_to_array));
        $this->assertTrue(array_key_exists('mese', $return_to_array));
        $this->assertTrue(array_key_exists('anno', $return_to_array));

        $this->assertEquals($payload['day'], $return_to_array['giorno']);
        $this->assertEquals($payload['month'], $return_to_array['mese']);
        $this->assertEquals($payload['year'], $return_to_array['anno']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getDatePayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('day', $this->stub->errors));
        $this->assertTrue(array_key_exists('month', $this->stub->errors));
        $this->assertTrue(array_key_exists('year', $this->stub->errors));

        unset($payload['day']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('day', $this->stub->errors));
        $this->assertTrue(! array_key_exists('month', $this->stub->errors));
        $this->assertTrue(! array_key_exists('year', $this->stub->errors));

        unset($payload['month']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('day', $this->stub->errors));
        $this->assertTrue(array_key_exists('month', $this->stub->errors));
        $this->assertTrue(! array_key_exists('year', $this->stub->errors));

        unset($payload['year']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('day', $this->stub->errors));
        $this->assertTrue(array_key_exists('month', $this->stub->errors));
        $this->assertTrue(array_key_exists('year', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getDatePayload(): array
    {
        return  [
            'day' => '01',
            'month' => '04',
            'year' => '2020'
        ];
    }
}