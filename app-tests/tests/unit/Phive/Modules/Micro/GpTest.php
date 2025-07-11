<?php

namespace Tests\Unit\Phive\Modules\Micro;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Phive\Modules\Micro\Wrappers\GpWrapper;

final class GpTest extends TestCase
{
    const RANGE = [
        '213.33.66.188',
        '84.50.110.135',
        '176.65.79.192/28',
      ];

    /**
     * @dataProvider dataProviderData
     */
    public function testClientIpBelongsToRange($clientIp, $isValidIp)
    {
        // Arrange
        $gbChild = new GpWrapper();
        $gbChild->setClientIp($clientIp);

        // Act
        $success = $gbChild->callClientIpBelongsToWhiteList(self::RANGE);

        // Assert
        $this->assertEquals($success, $isValidIp);
    }

    /**
     * @dataProvider dataProviderData
     */
    public function testWhiteListGpIps($clientIp, $isValidIp)
    {
        // Arrange
        $gbChild = $this->createWrapper();
        $gbChild->setClientIp($clientIp);

        // Act
        $success = $gbChild->callToWhiteListGpIpsIsSuccessfull(self::RANGE);

        // Assert
        $this->assertEquals($success, $isValidIp);
    }

    public function testWhiteListGpIpsWithEmptyRangeDontValidateIps()
    {
        // Arrange
        $gbChild = $this->createWrapper();
        $gbChild->setClientIp('event_not_an_ip');

        // Act
        $success = $gbChild->callToWhiteListGpIpsIsSuccessfull([]);

        // Assert
        $this->assertEquals(true, $success);
    }

    public function testWhiteListGpIpsWithInCliModeDontValidateIps()
    {
        // Arrange
        $gbChild = $this->createWrapper();
        $gbChild->setIsCli(true);

        // Act
        $success = $gbChild->callToWhiteListGpIpsIsSuccessfull(self::RANGE);

        // Assert
        $this->assertEquals(true, $success);
    }

    /**
     * @return array[]
     */
    public function dataProviderData()
    {
        return [
          ['84.50.110.135', true],
          ['84.50.110.131', false],
          [self::RANGE[0], true],
          [self::RANGE[1], true],
          ['176.65.79.192', true],
          ['176.65.79.193', true],
          ['176.65.79.194', true],
          ['176.65.79.195', true],
          ['176.65.79.196', true],
          ['176.65.79.197', true],
          ['176.65.79.198', true],
          ['176.65.79.199', true],
          ['176.65.79.200', true],
          ['176.65.79.201', true],
          ['176.65.79.202', true],
          ['176.65.79.203', true],
          ['176.65.79.204', true],
          ['176.65.79.205', true],
          ['176.65.79.206', true],
          ['176.65.79.207', true],
          ['176.65.79.208', false],
        ];
    }

    /**
     * @return \Tests\Unit\Phive\Modules\Micro\Wrappers\GpWrapper
     */
    private function createWrapper(): GpWrapper
    {
        return new GpWrapper();
    }
}