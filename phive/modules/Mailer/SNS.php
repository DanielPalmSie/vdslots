<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\Exception\InvalidSnsMessageException;

class SNS
{
    /**
     * The SnsClient instance.
     *
     * @var SnsClient
     */
    protected $client;

    public function __construct()
    {
        $config = phive('MailHandler2')->getSetting('aws_config');

        if (empty($config['SNS'])) {
            throw new Exception('SNS config not set');
        }

        $this->client = new SnsClient($config['SNS']);
    }

    /**
     * Method to test SNS and get the list of subscriptions
     *
     * @return \Aws\Result|bool
     */
    public function listSubscriptions()
    {
        try {
            return $this->client->listSubscriptions();
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Process the SNS message, validate and also handle Subscription Confirmation
     *
     * @return Message
     */
    protected function getMessage()
    {
        try {
            $message = Message::fromRawPostData();
            $validator = new MessageValidator();
        } catch (Exception $e) {
            http_response_code(404);
            error_log('SNS Error: ' . $e->getMessage());
            die();
        }

        // Validate the message and log errors if invalid.
        try {
            $validator->validate($message);
        } catch (InvalidSnsMessageException $e) {
            // Pretend we're not here if the message is invalid.
            http_response_code(404);
            error_log('SNS Message Validation Error: ' . $e->getMessage());
            die();
        }

        // Check the type of the message and handle the subscription.
        if ($message['Type'] === 'SubscriptionConfirmation') {
            // Confirm the subscription by sending a GET request to the SubscribeURL
            file_get_contents($message['SubscribeURL']);
            die();
        }

        return $message;
    }



    /**
     * Receive SNS messages from mail delivery topics (Not supporting other types of notifications)
     */
    public function receiveSESNotification()
    {
        $message = $this->getMessage();

        if ($message['Type'] === 'Notification') {
            phive('Mailer/SES')->processEvent(json_decode($message['Message'], true));
        } else {
            error_log("SNS message type not supported: " . var_export($message, true));
        }

    }

}