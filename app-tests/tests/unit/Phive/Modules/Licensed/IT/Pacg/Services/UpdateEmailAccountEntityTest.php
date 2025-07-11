<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\UpdateEmailAccountEntity;

/**
 * Class UpdateEmailAccountEntityTest
 */
class UpdateEmailAccountEntityTest extends AbstractServiceTest
{
    /**
     * @var UpdateEmailAccountEntity
     */
    protected $stub;

    /**
     * UpdateEmailAccountEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(UpdateEmailAccountEntity::class)->makePartial();
    }

    public function testSuccessSettingUpdateEmailAccountEntity()
    {
        $payload = $this->getUpdateEmailAccountPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('postaElettronica', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['email'], $return_to_array['postaElettronica']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getUpdateEmailAccountPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));

        unset($payload['email']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getUpdateEmailAccountPayload(): array
    {
        return [
            'account_code' => 4002,
            'email' => 'test@videoslots.com',
            'transaction_id' => time()
        ];
    }
}