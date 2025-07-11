<?php

namespace App\Classes\Mailer;

use App\Models\MailerQueue;
use App\Models\MailerQueueCrm;
use Illuminate\Support\Collection;
use Silex\Application;

interface MailerInterface
{
    /**
     * MailerInterface constructor.
     * @param Application $app
     * @param MailerQueue|MailerQueueCrm $queue
     */
    public function __construct($app, $queue);

    /**
     * Detect if we can send email to this address
     *
     * @param string $email
     * @return bool
     */
    public function shouldSendEmail($email): bool;

    /**
     * @param array $item
     * @return bool
     */
    public function canSendBulk($item);

    /**
     * @param array $item
     * @return mixed|array
     */
    public function sendItem($item);

    /**
     * @param Collection $items
     * @return mixed|array
     */
    public function sendBulk($items);

}
