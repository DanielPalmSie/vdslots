<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\ListDormantAccountsEntity;
use IT\Pacg\Types\DateType;

/**
 * Class ListDormantAccountsEntityTest
 */
class ListDormantAccountsEntityTest extends AbstractServiceTest
{
    /**
     * @var ListDormantAccountsEntity
     */
    protected $stub;

    /**
     * ListDormantAccountsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(ListDormantAccountsEntity::class)->makePartial();
    }

    public function testSuccessSettingListDormantAccountsEntity()
    {
        $payload = $this->getListDormantAccountsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('dataRichiesta', $return_to_array));
        $this->assertTrue(array_key_exists('inizio', $return_to_array));
        $this->assertTrue(array_key_exists('fine', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['start'], $return_to_array['inizio']);
        $this->assertEquals($payload['end'], $return_to_array['fine']);

        $this->assertInstanceOf(DateType::class, $this->stub->date_request);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getListDormantAccountsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('date_request', $this->stub->errors));
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(array_key_exists('end', $this->stub->errors));

        unset($payload['date_request']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('date_request', $this->stub->errors));
        $this->assertTrue(! array_key_exists('start', $this->stub->errors));
        $this->assertTrue(! array_key_exists('end', $this->stub->errors));

        unset($payload['start']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('date_request', $this->stub->errors));
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(! array_key_exists('end', $this->stub->errors));

        unset($payload['end']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('date_request', $this->stub->errors));
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(array_key_exists('end', $this->stub->errors));
    }

    public function testErrorToSetDateRequestException()
    {
        $payload = $this->getListDormantAccountsPayload();
        unset($payload['date_request']['day']);
        $this->expectException(\Exception::class);
        $this->stub->setDateRequest($payload['date_request']);
    }

    /**
     * @return array
     */
    private function getListDormantAccountsPayload(): array
    {
        return [
            'date_request' => [
                'day' => '01',
                'month' => '04',
                'year' => '2020'
            ],
            'start' => 1,
            'end' => 100,
            'transaction_id' => time()
        ];
    }
}