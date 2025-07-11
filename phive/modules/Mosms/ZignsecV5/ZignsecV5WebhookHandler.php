<?php

namespace Mosms\ZignsecV5;

use Mosms\MosmsLoggerTrait;

/**
 * Class to handle Sms webhooks of Zignsec V5 API
 *
 * @link https://developers.zignsec.com/guides/webhooks
 */
class ZignsecV5WebhookHandler
{
    use MosmsLoggerTrait;

    /**
     * SMS is queued for sending on Zignsec side
     */
    const DISPATCHED = 'dispatched';

    /**
     * SMS sending is failed
     */
    const FAILED = 'failed';

    /**
     * SMS sending is successful
     */
    const DELIVERED = 'delivered';

    public function handle(array $body): void
    {
        $this->logWebhook($body);
    }

    private function logWebhook(array $body): void
    {
        $status = $body['status'];

        $this->log('SMS webhook (ZignsecV5WebhookHandler)', [
            'webhook_payload ' => $body,
        ], 'debug');

        if ($status === self::FAILED) {
            $this->log('Failed SMS webhook (ZignsecV5WebhookHandler)', [
                'webhook_payload ' => $body,
            ], 'error');
        }
    }
}
