<?php

namespace App\Classes\Mailer;

use Silex\Provider\SwiftmailerServiceProvider;
use Swift_Mailer;
use Swift_Message;

class SMTP extends Mailer
{
    /** @var Swift_Mailer $service */
    private $service;

    /**
     * SparkPost constructor.
     * @param $app
     * @param $queue
     */
    public function __construct($app, $queue)
    {
        parent::__construct($app, $queue);

        $this->app->register(new SwiftmailerServiceProvider(), array(
            'swiftmailer.options' => [
                'transport' => getenv('MAIL_TRANSPORT'),
                'host' => getenv('MAIL_HOST'),
                'port' => getenv('MAIL_PORT'),
                'encryption' => getenv('MAIL_ENCRYPTION'),
                'username' => getenv('MAIL_USERNAME'),
                'password' => getenv('MAIL_PASSWORD')
            ]
        ));

        $this->service = $this->app['mailer'];
    }

    /**
     * @param $item
     * @param null $callback
     * @return array
     * @throws
     */
    public function sendItem($item, $callback = null)
    {
        $item = $this->queue::formatEmailFields($item);

        if (!empty($item['replacers'])) {
            $item['html'] = $this->replacer_repo->replaceKeywords($item['html'], $item['replacers']);
            $item['text'] = $this->replacer_repo->replaceKeywords($item['text'], $item['replacers']);
            $item['subject'] = $this->replacer_repo->replaceKeywords($item['subject'], $item['replacers']);
        }

        $message = Swift_Message::newInstance()
            ->setSubject($item['subject'])
            ->setFrom($item['from'], $item['from_name'])
            ->setTo($item['to'])
            ->setBody($item['text'])
            ->addPart($item['html'], 'text/html');

        return [$this->service->send($message)];
    }
}
