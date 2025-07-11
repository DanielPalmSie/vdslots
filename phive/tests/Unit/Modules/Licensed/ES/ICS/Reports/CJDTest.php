<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports;

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\CJD;
use ES\ICS\Reports\Info;
use Tests\Unit\TestPhiveBase;

class CJDTest extends TestPhiveBase
{
    /** @var \Phive|object */
    private $licensed;

    public function setUp(): void
    {
        parent::setUp();
        $this->createScenario();
    }

    /**
     * @dataProvider shouldSelectVersionProperlyDataProvider
     */
    public function testShouldSelectVersionProperly(
        string $expectedVersion,
        string $currentDate
    ): void {

        Carbon::setTestNow($currentDate);

        $report = new CJD(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => '2000-01-01',
                'frequency' => array_rand(array_keys(ICSConstants::FREQUENCY_VALUES)),
                'game_types' => [],
            ]
        );
        $this->assertEquals($expectedVersion, $report->getXmlVersion());
    }

    public function shouldSelectVersionProperlyDataProvider(): array
    {
        return [
            [Info::VERSIONS[2]['xmlVersion'], Info::VERSIONS[2]['endDateTime']],
            [Info::VERSIONS[3]['xmlVersion'], (new \DateTimeImmutable(Info::VERSIONS[2]['endDateTime']))
                ->modify('+1 day')->format('Y-m-d')]
        ];
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }
}
