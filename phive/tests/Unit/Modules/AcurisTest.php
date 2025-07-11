<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../../phive/phive.php';

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
abstract class AcurisTest extends TestCase
{
    protected $user;
    protected $module_version;
    protected $acuris_module;

    protected const PEP_FAILURE_SETTING = 'pep_failure';
    protected const SANCTION_LIST_FAILURE = 'sanction_list_failure';

    public function setUp(): void
    {
        $test_user_email = env('test_user_email') ?? 'test';
        $user_data = phive('SQL')->loadArray("SELECT id FROM users WHERE email LIKE '%" . $test_user_email . "%' ORDER BY id DESC LIMIT 1");
        $this->user = cu($user_data[0]['id']);
        $this->setModuleVersion();
        $this->acuris_module = phive("DBUserHandler/{$this->module_version}");
    }

    public function tearDown(): void
    {
        $this->user->deleteSetting(static::PEP_FAILURE_SETTING);
        $this->user->deleteSetting(static::SANCTION_LIST_FAILURE);
    }

    /**
     * @dataProvider responseDataProvider
     */
    public function testMapPEPResultToSetting($response, $assert_result)
    {
        $mapPEPResultToSetting = new ReflectionMethod($this->acuris_module, 'mapPEPResultToSetting');
        $mapPEPResultToSetting->setAccessible(true);
        $setting_value = $mapPEPResultToSetting->invoke($this->acuris_module, $response);
        $this->assertEquals($assert_result, $setting_value);
    }

    public function testCheckPEPSanctions()
    {
        $result_code = $this->acuris_module->checkPEPSanctions($this->user);

        $this->assertEquals("ALERT", $result_code);
        $this->assertEquals("0", $this->user->getSetting(static::PEP_FAILURE_SETTING));
        $this->assertEquals("0", $this->user->getSetting(static::SANCTION_LIST_FAILURE));
    }

    abstract protected function setModuleVersion(): void;
    abstract protected function responseDataProvider(): array;
}