<?php
require_once('MailConnector.php');

class SMTP extends MailConnector
{
    /**
     * The SesClient instance.
     *
     * @var Swift_Mailer
     */
    protected $client;

    /**
     * SMTP constructor.
     *
     * @param string $config_tag
     */
    public function __construct($config_tag = 'smtp_config')
    {
        parent::__construct($config_tag);

        $transport = (new Swift_SmtpTransport($this->config['host'], $this->config['port'], $this->config['ssl']))
            ->setUsername($this->config['username'])
            ->setPassword($this->config['password']);

        $this->client = new Swift_Mailer($transport);
    }

    /**
     * @param string|array $recipient_emails
     * @param string $reply_to
     * @param string $subject
     * @param string $html_body
     * @param string $plaintext_body
     * @param string $sender_email
     * @param string|null $sender_name
     * @param string $queued_at
     * @param null|int $user_id
     * @param bool $log_it
     * @param array|null $cc_emails
     * @param array|null $bcc_emails
     */
    public function sendEmail(
        $recipient_emails,
        $reply_to,
        $subject,
        $html_body,
        $plaintext_body,
        $sender_email,
        $sender_name = null,
        $queued_at = null,
        $user_id = null,
        $log_it = true,
        $cc_emails = null,
        $bcc_emails = null
    ) {

        $char_set = 'UTF-8';
        $message_id = phive()->uuid();

        if (!empty($this->config['tracking_url'])) {
            $html_body .= ' <img src="'. $this->config['tracking_url'] .'?mid=' .$message_id. '">';
        }

        try {
            $logger = new Swift_Plugins_Loggers_ArrayLogger();
            $this->client->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));

            $message = (new Swift_Message($subject))
                //->setFrom(['john@doe.com' => 'John Doe'])
                ->setFrom([$sender_email => 'Videoslots']) //TODO fix
                ->setReplyTo([$reply_to => 'Videoslots']) //TODO fix
                //->setTo(['receiver@domain.org', 'other@domain.org' => 'A name'])
                ->setTo(is_string($recipient_emails) ? [$recipient_emails] : $recipient_emails)
                ->setBody($html_body, 'text/html', $char_set)
                ->addPart($plaintext_body, 'text/plain', $char_set)
                ->setId($message_id ."@videoslots.com"); //TODO fix

            $extra['result'] = $this->client->send($message, $failures);
            if (!empty($failures)){
                $extra['failures'] = $failures;
            }
            $log = explode('<<', $logger->dump());
            $extra['response'] = trim(end($log));

        } catch (Exception $e) {
            $error = "The email was not sent. Error message: ".$e->getMessage();
            $message_id = '';
        }

        if ($log_it) {
            $to_address = is_string($recipient_emails) ? $recipient_emails : implode(',', $recipient_emails);
            if (!empty($error)) {
                $extra = $error;
            } else {
                $extra = [$cc_emails, $bcc_emails];
                $extra = array_unique($extra) === array(null) ? null : json_encode($extra);
            }
            $this->logMail($to_address, $this->config['supplier'], $message_id, $subject, $plaintext_body, $extra, $queued_at, phive()->hisNow(), $user_id);
        }

        return true;
    }

    /**
     * Only tracking open for now supported
     *
     * @param $message_id
     */
    public function processEvent($message_id)
    {
        list($tag, $message_id) = phive('SQL')->sanitizeString($message_id);

        $mail = phive('SQL')->loadAssoc('', 'mail_log', array('external_id' => $message_id));
        if (empty($mail['id'])) {
            return;
        }

        $extra = [
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        $this->logMailEvent($mail['id'], phive()->hisNow(), 'open', 200, json_encode($extra));
    }
}