<?php

require_once __DIR__ . '/PoolXBoxBase.php';

class PoolXMobileAppBoxBase extends PoolXBoxBase
{
    public function init(): void
    {
        if (empty($_GET['auth_token']) && !cu()) {
            http_response_code(401);
            exit();
        }
    }

    public function printHTML(): void
    {
        loadCss('/diamondbet/css/' . brandedCss() . 'poolx_mobile_app.css');     
        $this->loadPoolX(cu() ?: null);
    }
}