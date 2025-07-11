<?php

namespace App\Middleware\Sparkpost;

use App\Middleware\BaseMiddleware;

class BasicAuth extends BaseMiddleware
{
    /**
     * Check if Basic Auth is enabled
     * @return bool
     */
    public function enabled(): bool
    {
        return !empty($this->app['api_middlewares']['sparkpost']['basic-auth']);
    }

    /**
     * Check if request passes the Basic Auth validation
     * @return bool
     */
    public function allowed(): bool
    {
        $headers = $this->request->headers;

        return trim($headers->get('php-auth-user')) === trim(getenv('SPARKPOST_WEBHOOK_USER'))
            && trim($headers->get('php-auth-pw')) === trim(getenv('SPARKPOST_WEBHOOK_PASS'));
    }
}
