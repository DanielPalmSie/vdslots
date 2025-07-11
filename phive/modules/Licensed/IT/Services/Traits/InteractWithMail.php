<?php

namespace IT\Services\Traits;

trait InteractWithMail
{
    /**
     * Dispatch sendRawMail job to the queue
     *
     * @param string $subject
     * @param $body
     */
    public static function notify(string $subject, $body)
    {
        $available_classes = phive('MailHandler2')->getSetting('enable_email_error_log_classes');
        $should_send_email = in_array(static::class, $available_classes);
        $to = phive('MailHandler2')->getSetting('dev_support_mail');
        if(empty($to) || !$should_send_email){
            // No recipient has been setup or mail sending is blocked.
            return;
        }
        
        if (is_array($body)) {
            $body = json_encode($body, JSON_PRETTY_PRINT);
        }

        phive('Site/Publisher')->single(
            'it-critical-notifications',
            'MailHandler2',
            'sendRawMail',
            [$to, $subject, $body]
        );
    }
}
