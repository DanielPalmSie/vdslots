<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\LimitListType;
use IT\Pacg\Types\LimitType;

/**
 * Class LimitListTypeTest
 */
class LimitListTypeTest extends AbstractTypeTest
{
    /**
     * @var LimitListType
     */
    protected $stub;

    /**
     * LimitListTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(LimitListType::class)->makePartial();
    }

    public function testSuccessSettingLimitListType()
    {
        for ($amount = 1; $amount <= 10; $amount++) {
            $payload = $this->getLimitListPayload($amount);
            $this->stub->fill($payload);
            $return_to_array = $this->stub->toArray();
            $this->assertTrue(is_array($return_to_array));
            $this->assertCount($amount, $return_to_array);
            foreach ($this->stub->limits as $limits) {
                $this->assertInstanceOf(LimitType::class, $limits);
            }
        }
    }

    public function testSetLimitList()
    {
        $set_limit_list = self::getAccessibleMethod(LimitListType::class, 'setLimitList');
        $this->assertEquals(null, $this->stub->limits);
        $set_limit_list->invoke($this->stub);
        $this->assertTrue(is_array($this->stub->limits));

        $this->stub->limits = $this->getLimitListPayload()['limits'];

        $set_limit_list->invoke($this->stub);
        $this->assertTrue(is_array($this->stub->limits));
        $this->assertCount(1, $this->stub->limits);
        $this->assertInstanceOf(LimitType::class, end($this->stub->limits));
    }

    public function testGetLimitsList()
    {
        for ($amount = 1; $amount <= 10; $amount++) {
            $payload = $this->getLimitListPayload($amount);
            $this->stub->fill($payload);
            $result = $this->stub->getLimitList();

            $this->assertTrue(is_array($result));
            $this->assertCount($amount, $result);
        }
    }

    public function testGetLimitsListFail()
    {
        $payload = $this->getLimitListPayload();
        $this->stub->limits = $payload;
        $this->expectException(\Exception::class);
        $this->stub->getLimitList();
    }

    public function testGetLimitsAmount()
    {
        for ($amount = 0; $amount <= 10; $amount++) {
            $payload = $this->getLimitListPayload($amount);
            $this->stub->fill($payload);
            $amount_result = $this->stub->getNumberOfLimits($amount);
            $this->assertEquals($amount, $amount_result);
        }
    }

    /**
     * @param int $bonus_detail_amount
     * @return array
     */
    private function getLimitListPayload(int $bonus_detail_amount = 1): array
    {
        $return_bonus_detail = [];
        for ($i = 1; $i <= $bonus_detail_amount; $i++) {
            $return_bonus_detail[] = [
                'limit_type' => 1,
                'amount' => 2000
            ];
        }

        return ['limits' => $return_bonus_detail];
    }
}