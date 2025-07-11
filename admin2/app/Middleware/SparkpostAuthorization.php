<?php

namespace App\Middleware;

use App\Middleware\Sparkpost\BasicAuth;
use App\Middleware\Sparkpost\IpCheck;

class SparkpostAuthorization extends BaseMiddleware
{
    /**
     * Check if request is allowed
     * @return bool
     */
    public function allow(): bool
    {
        if (!$this->isSparkpostRoute()) {
            return true;
        }

        return $this->isIpOk() && $this->isBasiAuthOk();
    }

    /**
     * Handle IP whitelisted
     * @return bool
     */
    private function isIpOk(): bool
    {
        $ip_check = new IpCheck($this->app, $this->request);
        return !($ip_check->enabled() && !$ip_check->allowed());
    }

    /**
     * Handle basic auth
     * @return bool
     */
    private function isBasiAuthOk(): bool
    {
        $basic_auth = new BasicAuth($this->app, $this->request);
        return !($basic_auth->enabled() && !$basic_auth->allowed());
    }

    /**
     * Detect if we should apply the sparkpost checks on this request
     * @return bool
     */
    private function isSparkpostRoute(): bool
    {
        return strpos($this->request->getUri(), 'webhook/sparkpost') !== false;
    }
}
