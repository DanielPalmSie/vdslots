<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class MailConnector
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var array
     */
    protected $config;

    /**
     * MailConnector constructor.
     *
     * @param string $config_tag Name of the config tag in the array
     * @throws Exception
     */
    public function __construct($config_tag)
    {
        $this->mailer = phive('MailHandler2');

        $this->config = $this->mailer->getSetting($config_tag);

        if (empty($this->config)) {
            throw new Exception("{$config_tag} config not set");
        }
    }

    /**
     * @param $to_address
     * @param $supplier
     * @param $external_id
     * @param $subject
     * @param $body
     * @param $extra
     * @param null $queued_at
     * @param null $sent_at
     * @param int $user_id
     * @return mixed
     */
    protected function logMail($to_address, $supplier, $external_id, $subject, $body, $extra, $queued_at = null, $sent_at = null, $user_id = 0)
    {
        return phive('SQL')->insertArray(
            'mail_log',
            compact('to_address', 'supplier', 'external_id', 'subject', 'body', 'extra', 'queued_at', 'sent_at', 'user_id')
        );
    }

    /**
     * @param $mail_log_id
     * @param $executed_at
     * @param $type
     * @param $response_code
     * @param null $extra
     * @return mixed
     */
    protected function logMailEvent($mail_log_id, $executed_at, $type, $response_code, $extra = null){
        return phive('SQL')->insertArray(
            'mail_log_events',
            compact('mail_log_id', 'executed_at', 'type', 'response_code', 'extra')
        );
    }

    public function testEmail($recipient_emails = ['ricardo.ruiz@videoslots.com'])
    {
        $sender_email = 'notifications@videoslots.com';

        $supplier_name = $this->config['supplier_name'];

        $subject = 'SMTP test using supplier '. $supplier_name;
        $plaintext_body = 'This email was sent with using the supplier in the subject - '. $supplier_name;
        $html_body =  '<h1>'. $subject .'</h1>'.
            '<p>This email was sent with <a href="https://ssss.ssdsdsdsd.com/ses/">'.
            'using</a> the supplier <a href="https://ssss.sdsdsd.com/sdk-for-php/">'.
            'in the subject - '. $supplier_name .'</a>.</p>';

        $this->sendEmail($recipient_emails, $sender_email, $subject, $html_body, $plaintext_body, $sender_email, phive()->hisNow(), 343434);
    }
}