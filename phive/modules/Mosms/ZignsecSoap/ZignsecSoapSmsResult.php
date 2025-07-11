<?php

namespace Mosms\ZignsecSoap;

use Mosms\SmsResult;

class ZignsecSoapSmsResult extends SmsResult
{
    public function isSuccess(): bool
    {
        return $this->response_status == 0;
    }
}
