<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\ListSelfExcludedAccountsEntity;

/**
 * Class ListSelfExcludedAccountsEntityTest
 */
class ListSelfExcludedAccountsEntityTest extends AbstractServiceTest
{
    /**
     * @var ListSelfExcludedAccountsEntity
     */
    protected $stub;

    /**
     * ListSelfExcludedAccountsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(ListSelfExcludedAccountsEntity::class)->makePartial();
    }

    public function testSuccessSettingListSelfExcludedAccountsEntity()
    {
        $payload = $this->getListSelfExcludedAccountsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('inizio', $return_to_array));
        $this->assertTrue(array_key_exists('fine', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['start'], $return_to_array['inizio']);
        $this->assertEquals($payload['end'], $return_to_array['fine']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getListSelfExcludedAccountsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(array_key_exists('end', $this->stub->errors));

        unset($payload['start']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(! array_key_exists('end', $this->stub->errors));

        unset($payload['end']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('start', $this->stub->errors));
        $this->assertTrue(array_key_exists('end', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getListSelfExcludedAccountsPayload(): array
    {
        return [
            'start' => 1,
            'end' => 100,
            'transaction_id' => time()
        ];
    }
}