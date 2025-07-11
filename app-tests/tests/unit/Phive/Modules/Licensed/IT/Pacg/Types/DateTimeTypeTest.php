<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\DateTimeType;
use IT\Pacg\Types\DateType;
use IT\Pacg\Types\TimeType;

/**
 * Class DateTimeTypeTest
 */
class DateTimeTypeTest extends AbstractTypeTest
{
    /**
     * @var DateTimeType
     */
    protected $stub;

    /**
     * DateTimeTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(DateTimeType::class)->makePartial();
    }

    public function testSuccessSettingDateTimeType()
    {
        $payload = $this->getDateTimePayload();
        $this->stub->fill($payload);

        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('data', $return_to_array));
        $this->assertTrue(array_key_exists('ora', $return_to_array));

        $this->assertInstanceOf(DateType::class, $this->stub->date);
        $this->assertInstanceOf(TimeType::class, $this->stub->time);
    }

    public function testSetInvalidDataPayload()
    {
        $payload = $this->getDateTimePayload();
        unset($payload['date']['day']);
        $this->stub->fill($payload);
        $this->expectException(\Exception::class);
        $this->stub->setDateType($payload['date']);
    }

    public function testSetInvaliTimePayload()
    {
        $payload = $this->getDateTimePayload();
        unset($payload['time']['minutes']);
        $this->stub->fill($payload);
        $this->expectException(\Exception::class);
        $this->stub->setDateType($payload['time']);
    }

    /**
     * @return array
     */
    private function getDateTimePayload(): array
    {
        return [
            'date' => [
                'day' => '01',
                'month' => '04',
                'year' => '2020'
            ],
            'time' => [
                'hours' => '01',
                'minutes' => '05',
                'seconds' => '00'
            ]
        ];
    }
}