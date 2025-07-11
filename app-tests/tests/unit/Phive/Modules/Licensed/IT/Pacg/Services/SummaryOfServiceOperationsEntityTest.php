<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\SummaryOfServiceOperationsEntity;
use IT\Pacg\Types\DateType;

/**
 * Class SummaryOfServiceOperationsEntityTest
 */
class SummaryOfServiceOperationsEntityTest extends AbstractServiceTest
{
    /**
     * @var SummaryOfServiceOperationsEntity
     */
    protected $stub;

    /**
     * SummaryOfServiceOperationsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(SummaryOfServiceOperationsEntity::class)->makePartial();
    }

    public function testSuccessSettingSummaryOfServiceOperationsEntity()
    {
        $payload = $this->getSummaryOfServiceOperationsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertInstanceOf(DateType::class, $this->stub->date);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getSummaryOfServiceOperationsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('date', $this->stub->errors));

        unset($payload['date']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('date', $this->stub->errors));
    }

    public function testErrorToSetDateException()
    {
        $payload = $this->getSummaryOfServiceOperationsPayload();
        unset($payload['date']['day']);
        $this->expectException(\Exception::class);
        $this->stub->setDate($payload['date']);
    }

    /**
     * @return array
     */
    private function getSummaryOfServiceOperationsPayload(): array
    {
        return [
            'transaction_id' => time(),
            'date'=> [
                'day' => '01',
                'month' => '04',
                'year' => '2020'
            ],
        ];
    }
}