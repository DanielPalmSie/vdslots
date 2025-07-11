<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Services;

use IT\Services\TrasversalSelfExclusionService;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

class TrasversalSelfExclusionServiceTest extends Support
{
    /**
     * @var TrasversalSelfExclusionService
     */
    protected $stub;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->stub = \Mockery::mock(TrasversalSelfExclusionService::class)->makePartial();
    }

    /**
     * @dataProvider getSelfExclusionTypeProvider
     * @param int $time
     * @param int $return_type
     * @throws \ReflectionException
     */
    public function testGetSelfExclusionType(int $time, int $return_type)
    {
        $get_self_exclusion_type = self::getAccessibleMethod(
            TrasversalSelfExclusionService::class,
            'getSelfExclusionType'
        );
        $result = $get_self_exclusion_type->invokeArgs($this->stub, [$time]);
        $this->assertEquals($return_type, $result);
    }

    /**
     * @dataProvider getSelfExclusionTypeProvider
     * @param int $time
     * @param int $return_type
     * @throws \ReflectionException
     */
    public function testGetPayload(int $time, int $return_type)
    {
        self::setValueInPrivateProperty(
            $this->stub,
            TrasversalSelfExclusionService::class,
            'user',
                new class() {
                    public function getSetting($var)
                    {
                        return 'ABCDEFGHI';
                    }
                }
            );

        self::setValueInPrivateProperty(
            $this->stub,
            TrasversalSelfExclusionService::class,
            'management_type',
            0
        );

        self::setValueInPrivateProperty(
            $this->stub,
            TrasversalSelfExclusionService::class,
            'self_exclusion_time',
            $time
        );

        $expected_result = [
            'tax_code' => 'ABCDEFGHI',
            'self_exclusion_management' => 0,
            'self_exclusion_type' => $return_type
        ];

        $result = $this->stub->getPayload();

        $this->assertEquals($expected_result, $result);
    }

    public function getSelfExclusionTypeProvider(): array
    {
        return [
            [30, 2],
            [60, 3],
            [90, 4],
            [0, 1],
            [1, 0]
        ];
    }
}