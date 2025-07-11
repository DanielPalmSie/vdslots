<?php

namespace Mosms\ZignsecV5;

use Mosms\MosmsLoggerTrait;
use Mosms\SmsResult;
use Mosms\SmsSenderInterface;
use ZignSecV5;

/**
 * Class to send Sms via Zignsec V5 API
 *
 * @link https://developers.zignsec.com/products/two-factor-authentication)
 * @link https://test-gateway.zignsec.com/#/Two%20Factor%20Authentication
 */
class ZignsecV5SmsSender implements SmsSenderInterface
{
    use MosmsLoggerTrait;

    private ZignSecV5 $zignsec_v5;

    private array $settings;

    public function __construct(array $settings, ZignSecV5 $zignsec_v5)
    {
        $this->settings = $settings;
        $this->zignsec_v5 = $zignsec_v5;
    }

    public function sendSms(string $country_code, string $mobile, string $mobile_full, string $message): SmsResult
    {
        $payload = $this->getPayload($mobile_full, $message);

        $result = $this->getSmsResult(
            $this->zignsec_v5->getTwoFactorAuthSmsResult($payload)
        );

        $this->logRequest($payload, $result);

        return $result;
    }

    private function getPayload(string $mobile_full, string $message): array
    {
        $recipient_number = $this->getRecipientNumber($mobile_full);

        $payload = [
            'metadata' => [
                'from' => $this->settings['from'],
                'max_message_parts' => 1,
                'message' => $message,
                'to' => $recipient_number,
            ],
            'relay_state' => $recipient_number,
            'webhook' => $this->settings['webhook_url']
        ];

        return $payload;
    }

    private function getRecipientNumber(string $mobile_full): string
    {
        if (!str_contains($mobile_full, '+')) {
            $mobile_full = '+' . $mobile_full;
        }

        return $mobile_full;
    }

    private function getSmsResult(array $response): ZignsecV5SmsResult
    {
        $response_status_code = $response['data']['status'] ?? -1;

        if (is_string($response_status_code)) {
            $response_status_code = 200;
        }

        return new ZignsecV5SmsResult(json_encode($response), $response_status_code);
    }

    private function logRequest(array $body, ZignsecV5SmsResult $result): void
    {
        $this->log('SMS send request (ZignsecV5SmsSender)', [
            'payload' => $body,
            'response' => $result->getResponseBody(),
            'response_status_code' => $result->getResponseStatus(),
        ], 'debug');

        if ($result->isFailure()) {
            $this->log('Error during SMS sending (ZignsecV5SmsSender)', [
                'payload' => $body,
                'response' => $result->getResponseBody(),
                'response_status_code' => $result->getResponseStatus(),
            ], 'error');
        }
    }
}
