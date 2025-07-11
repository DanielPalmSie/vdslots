<?php

require_once __DIR__ . '/../Licensed/Libraries/GamStop/Single/GamStopSingle.php';
require_once __DIR__ . '/../Licensed/Libraries/GamStop/Batch/GamStopBatch.php';


/**
 * Class TestGamstop
 */
class TestGamstop extends TestPhive
{
    /** @var DBUserHandler $uh */
    private $uh;

    /** @var SQL $db */
    private $db;

    /** @var DBUser $user */
    private $user;

    /** @var GB|DK|SE */
    private $lic_module;
    
    function __construct()
    {
        $this->db = phive('SQL');
        $this->uh = phive('UserHandler');
    }

    /**
     * @param $user
     * @return array
     */
    public function testGamStopSingleV1($user): array
    {
        $user = ud($user);
        $settings = phive('Licensed/GB/GB')->getLicSetting('gamstop');
        $version = 'v1';

        try {
            $gamStop = new GamStopSingle($version, $settings['urls']['single'][$version], $settings['key'], $settings["timeout"]);
            $gamStop->setParams($user, phive()->uuid());
            $res = $gamStop->execute();

        } catch (Exception $e) {
            $res = [
                'error' => "{$e->getMessage()}",
                'statusCode' => 400
            ];
        }

        return $res;
    }

    /**
     * @param $user
     * @return array
     */
    public function testGamStopSingleV2($user): array
    {
        $user = ud($user);
        $settings = phive('Licensed/GB/GB')->getLicSetting('gamstop');

        $version = 'v2';

        try {
            $gamStop = new GamStopSingle($version, $settings['urls']['single'][$version], $settings['key'], $settings["timeout"]);
            $gamStop->setParams($user, phive()->uuid());
            $res = $gamStop->execute();

        } catch (Exception $e) {
            $res = [
                'error' => "{$e->getMessage()}",
                'statusCode' => 400
            ];
        }

        return $res;
    }


    /**
     * @param array $users
     * @return array
     */
    public function testGamStopBatchV2(array $users): array
    {

        $settings = phive('Licensed/GB/GB')->getLicSetting('gamstop');

        $version = 'v2';

        try {
            $gamStop = new GamStopBatch($version, $settings['urls']['batch'][$version], $settings['key'], $settings["timeout"]);
            $gamStop->setUsers($users);
            $res = $gamStop->execute();
        } catch (Exception $e) {
            $res = [
                'error' => "{$e->getMessage()}",
                'statusCode' => 400
            ];
        }

        return $res;
    }

    /**
     * Prepare user to be tested
     *
     * @param string $username
     * @param string $country
     * @throws 
     */
    private function prepareUser(string $username, string $country)
    {
        $user = cu($username);
        if (empty($user)) {
            throw new \Exception("User $username not found \n");
        }
        $user->updateData(['country' => $country]);
        $user->deleteSettings('lock-hours', 'lock-date', 'unlock-date', 'super-blocked', 'unexclude-date');

        $this->user = cu($username);
    }

    /**
     * Setup old self exclusion status
     *
     * @param string $status
     */
    private function setupOld(string $status)
    {
        if ($status === 'Y' || $status === 'P') {
            $this->uh->externalSelfExclude($this->user);
        } elseif ($status === 'N') {
            $this->uh->removeExternalSelfExclusion($this->user);
            $this->user->deleteSetting("last-{$this->lic_module->ext_exclusion_name}-check");
            $this->user->deleteSetting("cur-{$this->lic_module->ext_exclusion_name}");
        }
    }

    /**
     * Check if the expected status is returned
     *
     * @param string $old
     * @param string $new
     * @param bool $expected
     * @throws 
     */
    private function testStatus(string $old, string $new, bool $expected)
    {
        $this->setupOld($old);

        $res = $this->lic_module->hasExternalSelfExclusion($this->user, $new);
        if ($res !== $expected) {
            $res = (int)$res;
            throw new Exception("$old to $new should result in external excluded=$expected but got $res\n");
        }
    }

    /**
     * Test all external self exclusion status changes
     *
     * @param string $username
     * @param string $country
     * @return string
     * @throws 
     */
    public function testHasExternalSelfExclusionStatusChange(string $username, string $country): string
    {
        // throws exception if $country is not licensed
        $this->lic_module = phive("Licensed/$country/$country");
        $this->prepareUser($username, $country);

        $this->testStatus('Y', 'Y', true);
        // here on GB the normal flow is Y -> P -> N so Y->N is considered an error and we don't modify the exclusion status
        $this->testStatus('Y', 'N', $country === 'GB' ? true : false);
        $this->testStatus('N', 'N', false);
        $this->testStatus('N', 'Y', true);
        $this->testStatus('Y', 'P', false);
        $this->testStatus('P', 'P', false);
        $this->testStatus('P', 'N', false);    
        $this->testStatus('P', 'Y', true);    

        return 'All tests passed';
    }
}