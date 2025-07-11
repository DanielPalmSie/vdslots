<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\v3\JUC;
use Tests\Unit\TestPhiveBase;

class JUCTest extends TestPhiveBase
{
    private JUC $report;
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
        $this->assertInstanceOf(JUC::class, $this->report);
    }

    public function testXmlVersionIsGeneratedProperly(): void
    {
        $xmlVersion = $this->report->getXmlVersion();

        // we mock the report to skip data retrieval
        $mock = $this->getMockBuilder(JUC::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOperatorId', 'getStorageId', 'getBatchId', 'getPeriodEnd', 'getData'])
            ->getMock();
        $mock->method('getOperatorId')->willReturn('');
        $mock->method('getStorageId')->willReturn('');
        $mock->method('getBatchId')->willReturn('');
        $mock->method('getPeriodEnd')->willReturn($this->report->getPeriodEnd());
        $mock->method('getData')->willReturn([]);
        $xml = $mock->toXML();

        $this->assertStringContainsString("v$xmlVersion.xsd", $xml);
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }

    private function createSut(): void
    {
        $this->report = new JUC(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => '2000-02-01',
                'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                'game_types' => [],
            ]
        );
    }
}
