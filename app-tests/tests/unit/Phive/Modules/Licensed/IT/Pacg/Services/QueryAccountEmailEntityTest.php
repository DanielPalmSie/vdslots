<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\QueryAccountEmailEntity;

/**
 * Class QueryAccountEmailEntityTest
 */
class QueryAccountEmailEntityTest extends AbstractServiceTest
{
    /**
     * @var QueryAccountEmailEntity
     */
    protected $stub;

    /**
     * QueryAccountEmailEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(QueryAccountEmailEntity::class)->makePartial();
    }

    public function testSuccessSettingQueryAccountEmailEntity()
    {
        $payload = $this->getQueryAccountEmailPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getQueryAccountEmailPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
    }


    /**
     * @return array
     */
    private function getQueryAccountEmailPayload(): array
    {
        return [
            'account_code' => 4002,
        ];
    }
}