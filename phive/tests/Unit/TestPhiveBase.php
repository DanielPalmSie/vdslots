<?php
namespace Tests\Unit;

require_once __DIR__ . '/../../../phive/phive.php';
require_once __DIR__. '/../../../phive/modules/Test/TestPhive.php';

use PHPUnit\Framework\TestCase;

class TestPhiveBase extends TestCase
{
    protected \TestPhive $testPhive;
    protected string $module;

    public function setUp(): void
    {
        $this->testPhive = new \TestPhive($this->module ?? 'SQL');
    }


    /**
     * Wrapper function to call getTestPlayer function in TestPhive
     */
    function getTestPlayer(string $country = 'GB'): \DBUser
    {
        return $this->testPhive->getTestPlayer($country);
    }

    /**
     * Wrapper function to call resetPlayer function in TestPhive
     */
    function resetPlayer(\DBUser $user)
    {
        $this->testPhive->resetPlayer($user);
    }
}