<?php

namespace ES\ICS\Validation\Traits;

trait DeviceTrait
{
    /**
     * Device Identifier
     * actions where tag = "uagent"
     * @param string $uagent
     * @return string
     */
    public function getDeviceId(string $uagent): string
    {
        $device = explode('set uagent to ', $uagent);

        return array_pop($device);
    }

    /**
     * Device type
     * 'MO' => everything , 'PC' => PC+macintosh, 'TB' => ipad
     * @param  $equipment
     * @return mixed|string
     */
    public function getDeviceType($equipment)
    {
        $device_key = 'ICS.device_type.' . strtolower($equipment);

        return $this->getLicSetting($device_key, 'MO');

    }
}
