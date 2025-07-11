<?php
require_once('MailConnector.php');

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SES extends MailConnector
{
    /**
     * The SesClient instance.
     *
     * @var SesClient
     */
    protected $client;

    /**
     * SES constructor.
     *
     * @param string $config_tag
     * @throws Exception
     */
    public function __construct($config_tag = 'aws_config')
    {
        parent::__construct($config_tag);

        $this->client = new SesClient($this->config['SES']);
    }

    /**
     * Get the amount of emails left in the current quota for the day
     *
     * @return bool|mixed false if we get an exception
     */
    public function checkQuotas()
    {
        try {
            $result = $this->client->getSendQuota();
            return $result["Max24HourSend"] - $result["SentLast24Hours"];
        } catch (AwsException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * @param string|array $recipient_emails
     * @param string $reply_to
     * @param string $subject
     * @param string $html_body
     * @param string $plaintext_body
     * @param string $sender_email
     * @param string|null $sender_name
     * @param string|null $queued_at
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
        $configuration_set = 'Default';

        if (!empty($sender_name)) {
            $sender_email = '"'. $sender_name .'" <'. $sender_email .'>';
        }

        try {
            $args = [
                'Destination' => [
                    'ToAddresses' => is_string($recipient_emails) ? [$recipient_emails] : $recipient_emails,
                ],
                'ReplyToAddresses' => [$reply_to],
                'Source' => $sender_email,
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => $char_set,
                            'Data' => $html_body,
                        ],
                        'Text' => [
                            'Charset' => $char_set,
                            'Data' => $plaintext_body,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => $char_set,
                        'Data' => $subject,
                    ],
                ],
                'ConfigurationSetName' => $configuration_set,
            ];

            if (!empty($cc_emails)) {
                $args['Destination']['CcAddresses'] = $cc_emails;
            }

            if (!empty($bcc_emails)) {
                $args['Destination']['BccAddresses'] = $bcc_emails;
            }

            $result = $this->client->sendEmail($args);
            $message_id = $result['MessageId'];

        } catch (AwsException $e) {
            $error = "The email was not sent. Error message: ".$e->getAwsErrorMessage();
            $message_id = '';
        }

        if ($log_it) {
            $to_address = is_string($recipient_emails) ? $recipient_emails : implode(',', $recipient_emails);
            if (!empty($error)) {
                $extra = $error;
            } else {
                $extra = [$cc_emails, $bcc_emails];
                $extra = array_unique($extra) === array(null) ? null : $extra;
            }
            $this->logMail($to_address, 'aws', $message_id, $subject, $plaintext_body, json_encode($extra), $queued_at, phive()->hisNow(), $user_id);
        }

        return $message_id;
    }

    /**
     * TODO implement logic on bounce and complaint
     *
     * @param $event
     * @throws Exception
     */
    public function processEvent($event)
    {
        $mail = phive('SQL')->loadAssoc('', 'mail_log', array('external_id' => $event['mail']['messageId']));
        if (empty($mail['id'])) {
            return;
        }

        if ($event['eventType'] == 'Delivery') {
            $response_code = explode(' ', $event['delivery']['smtpResponse'])[0];
        } else {
            $response_code = 200;
        }

        $timestamp = empty($event[strtolower($event['eventType'])]['timestamp'])
            ? phive()->hisNow()
            : (new \DateTime($event[strtolower($event['eventType'])]['timestamp']))->format('Y-m-d H:i:s');

        $extra = $event;
        unset($extra['eventType']);
        unset($extra['mail']);

        $this->logMailEvent($mail['id'], $timestamp, $event['eventType'], $response_code, json_encode($extra));
    }
}