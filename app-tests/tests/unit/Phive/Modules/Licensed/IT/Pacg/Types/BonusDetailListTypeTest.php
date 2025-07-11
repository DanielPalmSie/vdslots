<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\BonusDetailType;

/**
 * Class BonusDetailListTypeTest
 */
class BonusDetailListTypeTest extends AbstractTypeTest
{
    /**
     * @var BonusDetailListType
     */
    protected $stub;

    /**
     * BonusDetailListTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(BonusDetailListType::class)->makePartial();
    }

    public function testSuccessSettingBonusDetailListType()
    {
        for ($amount = 1; $amount <= 10; $amount++) {
            $payload = $this->getBonusDetailListPayload($amount);
            $this->stub->fill($payload);
            $return_to_array = $this->stub->toArray();
            $this->assertTrue(is_array($return_to_array));
            $this->assertCount($amount, $return_to_array);
            foreach ($this->stub->bonus_detail as $bonus_detail) {
                $this->assertInstanceOf(BonusDetailType::class, $bonus_detail);
            }
        }
    }

    public function testSetBonusDetailList()
    {
        $set_bonus_detail_list = self::getAccessibleMethod(BonusDetailListType::class, 'setBonusDetailList');
        $this->assertEquals(null, $this->stub->bonus_detail);
        $set_bonus_detail_list->invoke($this->stub);
        $this->assertTrue(is_array($this->stub->bonus_detail));

        $this->stub->bonus_detail = $this->getBonusDetailListPayload()['bonus_detail'];

        $set_bonus_detail_list->invoke($this->stub);
        $this->assertTrue(is_array($this->stub->bonus_detail));
        $this->assertCount(1, $this->stub->bonus_detail);
        $this->assertInstanceOf(BonusDetailType::class, end($this->stub->bonus_detail));
    }

    public function testGetBonusDerailsList()
    {
        for ($amount = 1; $amount <= 10; $amount++) {
            $payload = $this->getBonusDetailListPayload($amount);
            $this->stub->fill($payload);
            $result = $this->stub->getBonusDetailsList();

            $this->assertTrue(is_array($result));
            $this->assertCount($amount, $result);
        }
    }

    public function testGetBonusDetailsListFail()
    {
        $payload = $this->getBonusDetailListPayload();
        $this->stub->bonus_detail = $payload;
        $this->expectException(\Exception::class);
        $this->stub->getBonusDetailsList();
    }

    public function testGetBonusDetailAmount()
    {
        for ($amount = 0; $amount <= 10; $amount++) {
            $payload = $this->getBonusDetailListPayload($amount);
            $this->stub->fill($payload);
            $amount_result = $this->stub->getNumberOfBonuses($amount);
            $this->assertEquals($amount, $amount_result);
        }
    }

    /**
     * @param int $bonus_detail_amount
     * @return array
     */
    private function getBonusDetailListPayload(int $bonus_detail_amount = 1): array
    {
        $return_bonus_detail = [];
        for ($i = 1; $i <= $bonus_detail_amount; $i++) {
            $return_bonus_detail[] = [
                'gaming_family' => '1',
                'gaming_type' => '1',
                'bonus_amount' => '30',
            ];
        }

        return ['bonus_detail' => $return_bonus_detail];
    }
}