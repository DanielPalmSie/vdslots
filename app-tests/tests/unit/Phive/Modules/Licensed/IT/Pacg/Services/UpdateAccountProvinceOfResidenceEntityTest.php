<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\UpdateAccountProvinceOfResidenceEntity;

/**
 * Class UpdateAccountProvinceOfResidenceEntityTest
 */
class UpdateAccountProvinceOfResidenceEntityTest extends AbstractServiceTest
{
    /**
     * @var UpdateAccountProvinceOfResidenceEntity
     */
    protected $stub;

    /**
     * UpdateAccountProvinceOfResidenceEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(UpdateAccountProvinceOfResidenceEntity::class)->makePartial();
    }

    public function testSuccessSettingUpdateAccountProvinceOfResidenceEntity()
    {
        $payload = $this->getUpdateAccountProvinceOfResidencePayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('provincia', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['province'], $return_to_array['provincia']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getUpdateAccountProvinceOfResidencePayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('province', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('province', $this->stub->errors));

        unset($payload['province']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('province', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getUpdateAccountProvinceOfResidencePayload(): array
    {
        return [
            'account_code' => 4002,
            'transaction_id' => time(),
            'province' => 'TO'
        ];
    }
}