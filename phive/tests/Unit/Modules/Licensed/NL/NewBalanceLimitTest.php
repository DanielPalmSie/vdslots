<?php
namespace Tests\Unit\Modules\Licensed\NL;

use Tests\Unit\TestPhiveBase;

/**
 * This is a dummy test class for a starter. Should be replaced with actual tests once unit testing is fully integrated in Phive.
 *
 * Class NewBalanceLimitTest
 * @package Tests\Unit\Modules\Licensed\NL
 */
final class NewBalanceLimitTest extends TestPhiveBase
{
    private $test_player;

    function setUp(): void
    {
        parent::setUp();

        $this->test_player = $this->testPhive->getTestPlayer('NL');
    }

    function tearDown(): void
    {
        $this->testPhive->cleanupTestPlayer($this->test_player->getId());
    }

    function testUserIsCreated()
    {
        /** Dummy tests here  */
        $num1 = $num2 = 100;
        $this->assertTrue($num1===$num2, 'Asserting that num1 and num2 matches.');
        $this->assertEquals($num1, $num2, 'Asserting that num1 and num2 are equal');

        $u_obj = $this->test_player;

        $this->assertNotEmpty($u_obj, 'Asserting that player is created in DB');
    }
}