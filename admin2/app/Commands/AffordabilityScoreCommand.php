<?php

namespace App\Commands;

use App\Controllers\UserProfileController;
use App\Models\User;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Eloquent\Builder;
use Silex\Application;
use Throwable;

/**
 * Fetch and set affordability score for GB users which:
 * - completed registration
 * - has a first deposit
 * - doesn't have the score index yet
 * - doesn't have setting bebettor_request_validation_error
 * - is not test account
 * The purpose of this command is to unblock NDL updating based on BeBettor affordability score.
 */
class AffordabilityScoreCommand extends Command
{
    protected static $defaultDescription = "Fetch and set affordability score for GB users which doesn't have the score index yet";

    protected const DEFAULT_CHUNK_SIZE = 100;
    protected const DEFAULT_MIN_REQUEST_INTERVAL_IN_MILLISECONDS = 300;

    private Application $app;
    private int $chunk_size;

    protected function configure()
    {
        $this->setName("affordability:score")
            ->addArgument(
                'chunk_size',
                InputArgument::OPTIONAL,
                "Query chunk size. Default " . self::DEFAULT_CHUNK_SIZE,
                static::DEFAULT_CHUNK_SIZE
            )
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                "Query limit.",
                0
            )->addArgument(
                'request_interval_in_milliseconds',
                InputArgument::OPTIONAL,
                "Request interval in milliseconds. Min " . static::DEFAULT_MIN_REQUEST_INTERVAL_IN_MILLISECONDS . " (~3 request/sec) BeBettor's API limit max 5 requests per sec.",
                static::DEFAULT_MIN_REQUEST_INTERVAL_IN_MILLISECONDS
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        loadPhive();
        $this->app = $this->getSilexApplication();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->chunk_size = (int)$input->getArgument('chunk_size');
        $limit = (int)$input->getArgument('limit');
        $request_interval_in_milliseconds = (int)$input->getArgument('request_interval_in_milliseconds');

        if ($limit > 0 && $this->chunk_size > $limit) {
            $this->chunk_size = $limit;
            $output->write("chunk_size can't be greater than $limit. chunk_size has been set to $limit",
                true);
        }

        if ($request_interval_in_milliseconds < static::DEFAULT_MIN_REQUEST_INTERVAL_IN_MILLISECONDS) {
            $output->write(
                "The request interval can't be less than " . static::DEFAULT_MIN_REQUEST_INTERVAL_IN_MILLISECONDS .
                " ms due to BeBettor API limitations (5 requests per second)",
                true
            );
            return 1;
        }

        $total_rows = $this->obtainUsersQuery()->count();
        $total_failed = 0;
        $total_successfully_updated = 0;
        $chunk_summary = "";
        $rows_to_execute = (!empty($limit) && $limit < $total_rows) ? $limit : $total_rows;

        if (empty($total_rows)) {
            $output->write("No records found to process.");
            return 0;
        }

        $time_estimation = $rows_to_execute / 2; // we send 2 requests per second (the delay is 500000 microseconds)

        $output->write("Chunk size $this->chunk_size.", true);
        $output->write("Total rows to be executed " . $rows_to_execute, true);
        $output->write("Estimated time: " . $time_estimation . " seconds.", true);
        $output->write("Warning: This list may also include users with invalid data.
        All of these users will be processed again at runtime, but only once within this iteration.", true);

        $start = microtime(true);
        for ($sh = 0; $sh <= 9; $sh++) {
            $output->write(PHP_EOL . "Processing shard #" . $sh . PHP_EOL, true);
            // exclude users with unsuccessful results to avoid an infinite loop
            $except_failed_user_ids = [];
            do {
                $to_process_query = $this->obtainUsersQuery($sh, $except_failed_user_ids);
                $to_process = $to_process_query->get();
                foreach ($to_process as $user) {
                    --$rows_to_execute;
                    try {
                        $request_started = microtime(true);
                        $response = UserProfileController::affordabilityCheck(
                            $this->app,
                            $user
                        );
                        $result = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

                        if (empty($result['success'])) {
                            ++$total_failed;
                            $error = $response->getContent();
                            $message = "User ID: {$user->id} BeBettor's request error: {$error}";
                            $user->repo->setSetting('bebettor_request_validation_error', $error, false);
                        } else {
                            ++$total_successfully_updated;
                            $message = "User ID: {$user->id}. Affordability score obtained.";
                        }
                        $request_elapsed = microtime(true) - $request_started;
                        $output->write($message . " Elapsed time: {$request_elapsed}s.", true);

                    } catch (Throwable $e) {
                        $except_failed_user_ids[] = $user->id;
                        ++$total_failed;
                        $request_elapsed = microtime(true) - $request_started;
                        $output->write(
                            "User ID: {$user->id} BeBettor's connection error: {$e->getMessage()}.
                            Elapsed time: {$request_elapsed}s.",
                            true
                        );
                    }
                    $chunk_summary = $rows_to_execute . " rows left. Total failed: $total_failed. Total successfully updated: $total_successfully_updated." . PHP_EOL;

                    if (!empty($limit) && $rows_to_execute === 0) {
                        $elapsed = microtime(true) - $start;
                        $output->write($chunk_summary, true);
                        $output->write("Query processing stopped due to limit $limit. Elapsed time: {$elapsed}s.", true);
                        return 0;
                    }

                    $request_interval_in_seconds = $request_interval_in_milliseconds / 1000;

                    if ($request_elapsed < $request_interval_in_seconds) {
                        $delay = ($request_interval_in_seconds - $request_elapsed) * 1000000;
                        usleep($delay);
                    }
                }

                if ($to_process->isNotEmpty()) {
                    $output->write($chunk_summary, true);
                }

            } while ($to_process_query->exists());
        }
        $elapsed = microtime(true) - $start;

        $output->write("Completed. Elapsed time: {$elapsed}s.", true);
        return 0;
    }

    /**
     * If the shard is not set, a global query is executed
     *
     * @param int|null $shard
     * @param array    $except_failed_user_ids exclude failed users
     *
     * @return Builder
     */
    private function obtainUsersQuery(?int $shard = null, array $except_failed_user_ids = []): Builder
    {
        if (is_int($shard)) {
            $query = User::sh($shard);
        } else {
            $query = User::query();
        }

        if (!empty($except_failed_user_ids)) {
            $query->whereNotIn('id', $except_failed_user_ids);
        }

        return $query->where('country', 'GB')
            ->where('username', 'not like', '%_closed')
            ->where('username', 'not like', 'closed_%')
            ->where('username', '!=', "")
            ->whereDoesntHave('settings', function ($query) {
                $query->whereIn('setting', ['test_account', 'bebettor_request_validation_error']);
            })
            ->whereHas('firstDeposit')
            ->whereDoesntHave('beBettor', function ($query) {
                $query->where('type', 'affordability')
                    ->where('solution_provider', 'BeBettor');
            })
            ->limit($this->chunk_size);
    }
}
