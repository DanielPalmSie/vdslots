<?php

namespace App\Middleware\Sparkpost;

use App\Middleware\BaseMiddleware;

class IpCheck extends BaseMiddleware
{
    public string $ips_file = __DIR__ . '/../../../.sparkpost.whitelist.ips';

    /**
     * Check if IP whitelisting is enabled
     * @return bool
     */
    public function enabled(): bool
    {
        $enabled = !empty($this->app['api_middlewares']['sparkpost']['ip-whitelisted-check']);
        if (!$enabled) {
            return false;
        }

        return is_readable($this->ips_file);
    }

    /**
     * Check if IP is in the ips file
     * @return bool
     */
    public function allowed(): bool
    {
        $ip_to_test = $this->request->getClientIp();
        $handle = fopen($this->ips_file, 'r');
        if (!$handle) {
            return false;
        }

        while (($ip = fgets($handle)) !== false) {
            if (trim($ip_to_test) === trim($ip)) {
                fclose($handle);
                return true;
            }
        }

        fclose($handle);
        return false;
    }
}
