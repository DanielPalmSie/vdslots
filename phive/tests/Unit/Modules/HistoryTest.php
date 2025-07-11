<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../../phive/phive.php';

use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * Recorders setting keys
     */
    protected const CLASS_KEY = '_class';
    protected const ONLY_KEY = '_only';
    protected const BRAND_KEY = '_brand';
    protected const DOMAIN_KEY = '_domain';
    protected const JURISDICTION_KEY = '_jurisdiction';

    protected static array $mockConfig = [
        'default' => [
            [
                self::CLASS_KEY => \History\KafkaRecorder::class,
                'broker' => '192.168.30.139:9092',
                self::ONLY_KEY => [
                    self::JURISDICTION_KEY => ['MGA'],
                    self::BRAND_KEY => ['videoslots', 'mrvegas'],
                ],
            ],
            [
                self::CLASS_KEY => \History\FileRecorder::class,
                self::ONLY_KEY => [
                    self::JURISDICTION_KEY => ['DGOJ', 'all'],
                    self::BRAND_KEY => 'videoslots',
                ],
                'filename' => '/var/www/log/logs/history.log',
            ],
            [
                self::CLASS_KEY => \History\FileRecorder::class,
                'filename' => '/var/www/log/logs/history.log',
                // duplicate _class just for test purpose to show that Recorder without _only is equal to all
            ],
        ],
    ];

    /**
     * @dataProvider recordersDataProvider
     */
    public function testGetRecorders(array $headers, int $expectedNumbersOfRecorders)
    {
        $stub = $this->getMockBuilder(\History::class)
            ->onlyMethods(['getSetting'])
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $stub->expects($this->any())
            ->method('getSetting')
            ->with('recorders', [])
            ->willReturn(static::$mockConfig);
        $recordersList = $stub->getRecorders('default', $headers);
        $obtainedNumbersOfRecorders = count($recordersList);

        $this->assertEquals($obtainedNumbersOfRecorders, $expectedNumbersOfRecorders);
    }

    /**
     * @return array[]
     */
    public function recordersDataProvider(): array
    {
        $domain = phive()->getSetting('full_domain');

        return [
            'MGA' => [
                [
                    self::JURISDICTION_KEY => 'MGA',
                    self::DOMAIN_KEY => $domain,
                    self::BRAND_KEY => 'videoslots',
                ],
                2,
            ],
            'DGOJ' => [
                [
                    self::JURISDICTION_KEY => 'DGOJ',
                    self::DOMAIN_KEY => $domain,
                    self::BRAND_KEY => 'videoslots',
                ],
                2,
            ],
            'all' => [
                [
                    self::JURISDICTION_KEY => 'all',
                    self::DOMAIN_KEY => $domain,
                    self::BRAND_KEY => 'videoslots',
                ],
                2,
            ],
        ];
    }
}
