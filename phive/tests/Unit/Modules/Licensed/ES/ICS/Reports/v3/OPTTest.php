<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\v3\OPT;
use Tests\Unit\TestPhiveBase;

class OPTTest extends TestPhiveBase
{
    private OPT $report;
    /** @var \Phive|object */
    private $licensed;

    public function setUp(): void
    {
        parent::setUp();
        $this->createScenario();
        $this->createSut();
    }

    public function testReportWasInstantiatedOk(): void
    {
        $this->assertInstanceOf(OPT::class, $this->report);
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }

    private function createSut(): void
    {
        $this->report = new OPT(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => '2999-12-31',
                'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                'game_types' => [],
            ]
        );
    }
}
