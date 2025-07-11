<?php


namespace App\Commands;

use App\Classes\Mailer\MailerInterface;
use App\Extensions\Database\FManager as DB;
use App\Helpers\Common;
use App\Models\MailerQueue;
use App\Models\MailerQueueCrm;
use App\Providers\ConfigServiceProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Ivoba\Silex\Command\Command;
use PDO;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class is responsible to send queued emails through Mandrill api.
 * Class MailerCommand
 * @package App\Commands
 */
class MailerCommand extends Command
{
    /** @var int Legacy ported version, the one use in phive */
    const LEGACY = 1;
    /** @var int CRM tool version */
    const CRM = 2;

    /** @var MailerQueue|MailerQueueCrm */
    private $queue;
    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;
    /** @var int $version */
    private $version = 1;
    /** @var integer $default_limit */
    private $default_limit = 500; //Low default as we will send only newsletters to begin with
    /** @var Application $app */
    private $app;
    /** @var int $delete_limit // How many rows to delete in one go. */
    private $delete_limit = 3000;
    /** @var MailerInterface $provider // used to enforce a provider for all priorities */
    private $provider;
    /** @var bool $newsletter_only */
    private $newsletter_only = true;

    public function __construct($name = null)
    {
        $this->app = new Application();

        collect([
            BASE_DIR . "/config/" . env('APP_ENV') . ".php",
            BASE_DIR . "/config/local.php"
        ])->each(function ($file) {
            return file_exists($file) ? $this->app->register(new ConfigServiceProvider($file)) : false;
        });

        if (empty($this->app['mailer.provider'])) {
            throw new \Exception(" mailer.provider is not defined in config/" . env('APP_ENV') . ".php");
        }

        if (empty($this->app['mailer.provider']['default'])) {
            throw new \Exception("Default email provider is required.");
        }

        parent::__construct($name);
    }

    /**
     * Initialize the mailer.
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName("crm:mailer:send")
            ->setDescription("Send newsletter emails.")
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                "Options: 1(legacy), 2(crm). Default is 1"
            )
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                "Number of emails to send at once. Default is {$this->default_limit}"
            )
            ->addArgument(
                'debug',
                InputArgument::OPTIONAL,
                "Print the result"
            )
            ->addArgument(
                'newsletter_only',
                InputArgument::OPTIONAL,
                "Options: 1(true), 0(false). Default is 1."
            )
            ->addArgument(
                'provider',
                InputArgument::OPTIONAL,
                "Options: SparkPost, SMTP. Use this to enforce a provider for ALL priorities. Default is configured in config/{env}.php mailer.provider"
            );
    }

    /**
     * @param string $provider
     * @return MailerInterface
     */
    protected function loadProvider($provider)
    {
        $provider = "App\Classes\Mailer\\" . $provider;
        $provider = new $provider($this->app, $this->queue);

        return $provider;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->version = $input->getArgument('version') ?? self::LEGACY;
        $this->queue = (int)$this->version === self::LEGACY ? new MailerQueue() : new MailerQueueCrm();

        if (!empty($provider = $input->getArgument('provider'))) {
            $this->provider = $this->loadProvider($provider);
        }

        if (!is_null($input->getArgument('newsletter_only'))) {
            $this->newsletter_only = $input->getArgument('newsletter_only') == 1;
        }

        $this->exec();

        return 0;
    }

    /**
     * Detect if current email is newsletter
     *
     * @param null $priority
     * @return bool
     */
    private function isNewsletter($priority = null): bool
    {
        return !((int)$this->version === self::LEGACY) || $priority === 3;
    }

    /**
     * @param null $priority
     * @return MailerInterface
     */
    private function getProviderForPriority($priority = null)
    {
        // provider was enforced from command arguments
        if (!empty($this->provider)) {
            return $this->provider;
        }

        $provider = $this->app['mailer.provider']['priority_map'][$priority] ?? $this->app['mailer.provider']['default'];

        return $this->loadProvider($provider);
    }

    /**
     * @throws Exception
     */
    private function exec()
    {
        $start_time = Carbon::now()->toTimeString();
        Common::dumpTbl("mailer-send-start-{$start_time}", Carbon::now()->toDateTimeString());

        $emails = $this->getQueuedItems($this->input->getArgument('limit') ?? $this->default_limit);

        $emails
            ->groupBy('priority')
            ->each(function($items, $priority) {
                /** @var Collection $items */
                /** @var MailerInterface $mail_provider */
                try {
                    $mail_provider = $this->getProviderForPriority($priority);
                } catch (\Exception $e) {
                    phive('Logger')
                        ->getLogger('cron')
                        ->info("Sparkpost error while getting provider", $e->getMessage());
                }

                if (!$mail_provider) {
                    return false;
                }

                $items
                    ->filter(function ($item) use ($mail_provider) {
                        if (!$mail_provider->shouldSendEmail($item['to'])) {
                            return false;
                        }

                        if ($mail_provider->canSendBulk($item)) {
                            return true;
                        }

                        $response = $mail_provider->sendItem($item);
                        $this->handleResponse($item, $response);

                        return false; // remove from list because it was sent just now
                    })
                    ->groupBy('messaging_campaign_id')
                    ->each(function ($items) use ($mail_provider) {

                        $response = $mail_provider->sendBulk($items);
                        foreach ($items as $item) {
                            $this->handleResponse($item, $response);
                        }
                    });
            });

        Common::dumpTbl("mailer-send-end-{$start_time}", Carbon::now()->toDateTimeString());
    }

    /**
     * TODO this is the original query, we want the same here
     *
     * $q = "SELECT * FROM `$db_mailer_queue` " . "WHERE `attempts` < $attempts_limit ";
     *   if ($priority !== null) {
     *   $q .= "AND `priority` = $priority ";
     *   }
     *   if (!empty($ss['IGNORE_NEWSLETTERS'])) {
     *   $q .= "AND `priority` != ". MailHandler2::PRIORITY_NEWSLETTER ." ";
     *   }
     *   $q .= "ORDER BY `priority` ASC , `time_queued` ASC " . "LIMIT $limit";
     * @param integer $limit
     * @return Collection
     * @throws Exception
     */
    private function getQueuedItems($limit)
    {
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);

        $result = $this->queue->getPrioritized($limit, $this->newsletter_only ? 3 : null);

        //We remove for now, as we don't mind retrying newsletters ATM
        $this->deleteQueuedEmails($result);

        return $result;
    }

    /**
     * Delete the pending mails from the database. Bulk deleting the emails by using a limit how many to delete in one go.
     *
     * @param Collection $result
     * @throws Exception
     */
    private function deleteQueuedEmails($result)
    {
        $key = $this->queue->getPrimaryKey();

        // creating groups of pending mails result ids by using $this->delete_limit
        // like this we are creating bulk delete limit for example deleting everything on result limit by $this->delete_limit.
        $result_as_array = Arr::pluck($result->toArray(), $key);
        $group_of_ids_to_remove = array_chunk($result_as_array, $this->delete_limit);

        foreach ($group_of_ids_to_remove as $ids_group) {
            DB::table($this->queue->getTable())
                ->whereIn($key, $ids_group)
                ->delete();
        }
    }

    /**
     * @param $item
     * @param $response
     * @throws Exception
     */
    private function handleResponse($item, $response)
    {
        if ($this->input->hasArgument('debug')) {
            $this->output->writeln("ID:{$item[$this->queue->getPrimaryKey()]} " . json_encode($response));
        }

        //TODO check on phive what do we do after

        //TODO we check the customer and unsuscribe if error i log for now

        $this->queue->postSend($item, $response);
    }

}
