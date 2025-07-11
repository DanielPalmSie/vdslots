<?php

namespace Mosms;

interface SmsSenderInterface
{
    public function sendSms(string $country_code, string $mobile, string $mobile_full, string $message): SmsResult;
}
