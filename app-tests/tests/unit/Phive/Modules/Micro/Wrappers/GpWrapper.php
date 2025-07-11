<?php

namespace Tests\Unit\Phive\Modules\Micro\Wrappers;

require_once __DIR__ . '/../../../../../../../phive/modules/Micro/Gp.php';
require_once __DIR__ . '/../../../../../../../phive/modules/Micro/Exceptions/IpIsOutOfRangeException.php';

class GpWrapper extends \Gp
{
    private $clientIp;

    private $isCli;

    public function preProcess()
    {
        return $this;
    }

    public function setDefaults()
    {
        return $this;
    }

    protected function _getUrl(
      $p_mGameId,
      $p_sLang = '',
      $p_sTarget = '',
      $show_demo = false
    ) {
        return "";
    }

    /**
     * @param $p_mResponse
     *
     * @return void
     */
    protected function _response($p_mResponse)
    {
        // the whole class is kind of mock, and we need this method only as abstract method implementation
    }

    public function callToWhiteListGpIpsIsSuccessfull(array $p_aWhitelistedGpIps)
    {
        try {
            $this->_whiteListGpIps($p_aWhitelistedGpIps);

            return true;
        } catch (\IpIsOutOfRangeException $exception) {
            return false;
        }
    }

    public function callClientIpBelongsToWhiteList(array $p_aWhitelistedGpIps)
    {
        return $this->clientIpBelongsToWhiteList($p_aWhitelistedGpIps);
    }

    public function setClientIp(string $clientIp)
    {
        $this->clientIp = $clientIp;
    }

    public function setIsCli(bool $isCli)
    {
        $this->isCli = $isCli;
    }

    protected function getIsCli()
    {
        return $this->isCli;
    }

    protected function getClientIp()
    {
        return $this->clientIp;
    }

    protected function terminate(string $message)
    {
        throw new \IpIsOutOfRangeException($message);
    }
}