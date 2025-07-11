<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\TimeType;

/**
 * Class TimeTypeTest
 */
class TimeTypeTest extends AbstractTypeTest
{
    /**
     * @var TimeType
     */
    protected $stub;

    /**
     * TimeTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(TimeType::class)->makePartial();
    }

    public function testSuccessSettingDateType()
    {
        $payload = $this->getTimePayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('ore', $return_to_array));
        $this->assertTrue(array_key_exists('minuti', $return_to_array));
        $this->assertTrue(array_key_exists('secondi', $return_to_array));

        $this->assertEquals($payload['hours'], $return_to_array['ore']);
        $this->assertEquals($payload['minutes'], $return_to_array['minuti']);
        $this->assertEquals($payload['seconds'], $return_to_array['secondi']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getTimePayload();;
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);

        $this->assertTrue(array_key_exists('hours', $this->stub->errors));
        $this->assertTrue(array_key_exists('minutes', $this->stub->errors));
        $this->assertTrue(array_key_exists('seconds', $this->stub->errors));

        unset($payload['hours']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);

        $this->assertTrue(array_key_exists('hours', $this->stub->errors));
        $this->assertTrue(! array_key_exists('minutes', $this->stub->errors));
        $this->assertTrue(! array_key_exists('seconds', $this->stub->errors));

        unset($payload['minutes']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);

        $this->assertTrue(array_key_exists('hours', $this->stub->errors));
        $this->assertTrue(array_key_exists('minutes', $this->stub->errors));
        $this->assertTrue(! array_key_exists('seconds', $this->stub->errors));

        unset($payload['seconds']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);

        $this->assertTrue(array_key_exists('hours', $this->stub->errors));
        $this->assertTrue(array_key_exists('minutes', $this->stub->errors));
        $this->assertTrue(array_key_exists('seconds', $this->stub->errors));

    }

    /**
     * @return array
     */
    private function getTimePayload()
    {
        return  [
            'hours' => '01',
            'minutes' => '05',
            'seconds' => '00'
        ];
    }
}