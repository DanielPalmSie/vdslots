<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\BonusDetailType;

/**
 * Class BonusDetailTypeTest
 */
class BonusDetailTypeTest extends AbstractTypeTest
{
    /**
     * @var BonusDetailType
     */
    protected $stub;

    /**
     * BonusDetailTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(BonusDetailType::class)->makePartial();
    }

    public function testSuccessSettingBonusDetailType()
    {
        $payload = $this->getBonusDetailPayload();;
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('famigliaGioco', $return_to_array));
        $this->assertTrue(array_key_exists('tipoGioco', $return_to_array));
        $this->assertTrue(array_key_exists('importo', $return_to_array));

        $this->assertEquals($payload['gaming_family'], $return_to_array['famigliaGioco']);
        $this->assertEquals($payload['gaming_type'], $return_to_array['tipoGioco']);
        $this->assertEquals($payload['bonus_amount'], $return_to_array['importo']);

    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getBonusDetailPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('gaming_family', $this->stub->errors));
        $this->assertTrue(array_key_exists('gaming_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_amount', $this->stub->errors));

        unset($payload['gaming_family']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('gaming_family', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gaming_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_amount', $this->stub->errors));

        unset($payload['gaming_type']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('gaming_family', $this->stub->errors));
        $this->assertTrue(array_key_exists('gaming_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_amount', $this->stub->errors));

        unset($payload['bonus_amount']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('gaming_family', $this->stub->errors));
        $this->assertTrue(array_key_exists('gaming_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_amount', $this->stub->errors));
    }

    public function testBonusDetailPayloadValidateFail()
    {
        $payload = $this->getBonusDetailPayload();
        $payload['gaming_family'] = -1;
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('gaming_family', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gaming_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_amount', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getBonusDetailPayload(): array
    {
        return [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '30',
        ];
    }
}