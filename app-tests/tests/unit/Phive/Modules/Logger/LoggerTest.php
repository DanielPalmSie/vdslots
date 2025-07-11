<?php

namespace Tests\Unit\Phive\Modules\Logger;

use Mockery\MockInterface;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\Phive\Modules\Logger\Wrappers\LoggerWrapper;

final class LoggerTest extends TestCase
{
    /**
     * @test
     */
    public function array_as_context_is_valid()
    {
        // Arrange
        $sut = $this->createLogger();

        // Act
        $this->expectContextEquals(["1"], $sut->getMock());
        $success = $sut->info("tournament", ["1"]);

        // Assert
        $this->assertTrue($success);
    }

    /**
     * @test
     */
    public function missing_context_is_valid()
    {
        // Arrange
        $sut = $this->createLogger();

        // Act
        $this->expectContextEquals([], $sut->getMock());
        $success = $sut->info("tournament");

        // Assert
        $this->assertTrue($success);
    }

    /**
     * @test
     */
    public function null_as_context_is_valid()
    {
        // Arrange
        $sut = $this->createLogger();

        // Act
        $this->expectContextEquals([], $sut->getMock());
        $success = $sut->info("tournament", null);

        // Assert
        $this->assertTrue($success);
    }

    /**
     * @test
     */
    public function literal_as_context_is_valid()
    {
        // Arrange
        $sut = $this->createLogger();

        // Act
        $this->expectContextEquals([4], $sut->getMock());
        $success = $sut->info("tournament", 4);

        // Assert
        $this->assertTrue($success);
    }

    private function createLogger(): LoggerWrapper
    {
        $logger = new LoggerWrapper();
        $monologLoggerMock = \Mockery::mock("monologLogger", LoggerInterface::class);
        $logger->setLogger($monologLoggerMock);

        return $logger;
    }

    private function expectContextEquals($expectedContext, MockInterface $mock): void
    {
        $mock->shouldReceive('log')
          ->withArgs([MonologLogger::INFO, "tournament", $expectedContext])
          ->once()
          ->andReturnTrue();
    }
}