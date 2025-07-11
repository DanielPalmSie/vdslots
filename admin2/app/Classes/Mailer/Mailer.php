<?php

namespace App\Classes\Mailer;

use App\Models\MailerQueue;
use App\Models\MailerQueueCrm;
use App\Models\User;
use App\Repositories\ReplacerRepository;
use Exception;
use Illuminate\Support\Collection;
use Silex\Application;

abstract class Mailer implements MailerInterface
{
    /** @var bool $bulk_capability */
    public $bulk_capability = false;
    /** @var ReplacerRepository $replacer_repo */
    public $replacer_repo;
    /** @var MailerQueue|MailerQueueCrm $queue */
    public $queue;
    /** @var Application $app */
    public $app;

    /**
     * Mailer constructor.
     * @param $app
     * @param $queue
     */
    public function __construct($app, $queue)
    {
        $this->app = $app;
        $this->queue = $queue;
        $this->replacer_repo = new ReplacerRepository();
    }

    /**
     * Detect if we can send email to this address
     *
     * @param string $email
     * @return bool
     */
    public function shouldSendEmail($email): bool
    {
        return !in_array(env('APP_ENV'), ['dev', 'test']) || in_array($email, $this->app['test_emails'] ?? [], true);
    }

    /**
     * @param array $item
     * @return bool
     */
    public function canSendBulk($item)
    {
        return $this->bulk_capability && !empty($item['replacers']);
    }

    /**
     * @param Collection $items
     * @return array|mixed|void
     * @throws Exception
     */
    public function sendBulk($items)
    {
        throw new Exception('Send bulk is not supported');
    }

}
