<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../phive/phive.php';

use PHPUnit\Framework\TestCase;

/**
 * run:  cd phive && php ./vendor/bin/phpunit tests/Unit/Modules/LoggerTest.php
 */
class LoggerTest extends PhiveModuleTest
{
    private $logger;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->logger = phive('Logger');
    }

    public function testMultilevelArray()
    {
        $data = [
            'name' => 'John Doe',
            'age' => 30,
            'address' => '123 Main St',
            'details' => [
                'surname' => 'Doe',
                'dob' => '1990-01-01',
                'contact' => [
                    'email' => 'john.doe@example.com',
                    'phone' => '123-456-7890',
                    'address' => '456 Secondary St',
                ],
            ],
        ];

        $newData = $this->logger->clearSensitiveFields($data);

        // Assertions
        $this->assertEquals('Jo****oe', $newData['name']);
        $this->assertEquals(30, $newData['age']);
        $this->assertEquals('12*******St', $newData['address']);
        $this->assertEquals('Do*', $newData['details']['surname']);
        $this->assertEquals('19******01', $newData['details']['dob']);
        $this->assertEquals('jo****************om', $newData['details']['contact']['email']);
        $this->assertEquals('12********90', $newData['details']['contact']['phone']);
        $this->assertEquals('45************St', $newData['details']['contact']['address']);
    }

    public function testEmptyArray()
    {
        $data = [];

        $newData = $this->logger->clearSensitiveFields($data);

        // Assertions
        $this->assertEmpty($newData);
    }

    public function testArrayWithoutSensitiveFields()
    {
        $data = [
            'ref' => 12345,
            'zipcode' => '123-456-7890',
        ];

        $newData = $this->logger->clearSensitiveFields($data);

        // Assertions
        $this->assertEquals(12345, $newData['ref']);
        $this->assertEquals('123-456-7890', $newData['zipcode']);
    }

    public function testArrayWithSomeSensitiveFields()
    {
        $data = [
            'name' => 'Jane Doe',
            'ref' => 12345,
            'dob' => '1992-02-02',
            'password' => 'P@ssw0rd',
        ];

        $newData = $this->logger->clearSensitiveFields($data);

        // Assertions
        $this->assertEquals('Ja****oe', $newData['name']);
        $this->assertEquals(12345, $newData['ref']);
        $this->assertEquals('19******02', $newData['dob']);
        $this->assertEquals('P@****rd', $newData['password']);
    }

    public function testNestedArrayWithMixedContent()
    {
        $data = [
            'profile' => [
                'name' => 'Alice',
                'info' => [
                    'surname' => 'Smith',
                    'dob' => '1985-05-05',
                    'location' => [
                        'address' => '789 Tertiary St',
                        'city' => 'Somewhere',
                    ],
                ],
            ],
        ];

        $newData = $this->logger->clearSensitiveFields($data);

        // Assertions
        $this->assertEquals('Al***', $newData['profile']['name']);
        $this->assertEquals('Sm***', $newData['profile']['info']['surname']);
        $this->assertEquals('19******05', $newData['profile']['info']['dob']);
        $this->assertEquals('78***********St', $newData['profile']['info']['location']['address']);
        $this->assertEquals('Somewhere', $newData['profile']['info']['location']['city']);
    }

    public function testEdgeValues()
    {
        // String input
        $data = 'This is a string';
        $newData = $this->logger->clearSensitiveFields($data);
        $this->assertEquals('This is a string', $newData);

        // Null input
        $data = null;
        $newData = $this->logger->clearSensitiveFields($data);
        $this->assertNull($newData);

        // Object input
        $data = (object) [
            'name' => 'Object Name',
            'age' => 40,
            'address' => 'Object Address',
        ];
        $newData = $this->logger->clearSensitiveFields($data);
        $this->assertEquals('Ob*******me', $newData['name']);
        $this->assertEquals(40, $newData['age']);
        $this->assertEquals('Ob**********ss', $newData['address']);
    }

    public function testLogMethodCallsClearSensitiveFields()
    {
        $loggerMock = $this->mockPhiveModule($this->logger, ['clearSensitiveFields']);

        $context = [
            'name' => 'John Doe',
            'ref' => 12345,
            'message' => 'Test message',
        ];

        $loggerMock->expects($this->once())
            ->method('clearSensitiveFields')
            ->with($context);

        $loggerMock->info('Test message', $context); // INFO or above should clear sensitive data
    }

    public function testLogLevelDebugDoesNotCallClearSensitiveFields()
    {
        $loggerMock = $this->mockPhiveModule($this->logger, ['clearSensitiveFields']);

        $context = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'message' => 'Test message',
        ];

        $loggerMock->expects($this->never())
            ->method('clearSensitiveFields');

        $loggerMock->debug('Test message', $context); // Debug should not clear sensitive data
    }
}
