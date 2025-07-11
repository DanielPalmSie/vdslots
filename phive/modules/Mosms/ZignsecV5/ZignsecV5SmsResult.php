<?php

namespace Mosms\ZignsecV5;

use Mosms\SmsResult;

class ZignsecV5SmsResult extends SmsResult
{
    public function isSuccess(): bool
    {
        return $this->response_status === 200;
    }
}
