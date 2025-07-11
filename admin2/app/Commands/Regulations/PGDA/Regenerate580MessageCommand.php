<?php

declare(strict_types=1);

namespace App\Commands\Regulations\PGDA;

use IT\Services\AAMSSession\AAMSSessionService;
use IT\Services\GameExecutionCommunicationService as GECS;
use IT\Pgda\Codes\ReturnCode as PgdaReturnCode;
use Carbon\Carbon;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Exception;
use InvalidArgumentException;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\LogicException;

class Regenerate580MessageCommand extends Command
{
    private const FILE_FORMAT_DATE_TIME = 'Ymd_His';
    private const MICRO_SEC_DELAY = 500000;
    private const ALREADY_CLOSED_SESSION = 1700;
    private const NUMBER_OF_DAYS_WINDOW = 7;

    private $app;
    private Carbon $start_date;
    private Carbon $end_date;
    private $it;
    private GECS $gecs;
    private int $sessions_closed = 0;
    private int $messages_successful = 0;
    private string $log_file_name = "580-game-execution-communication_%s.log";

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName("regulations:pgda:regenerate580")
            ->setDescription("Regenerate 580 messages for Italian users' sessions left unmarked")
            ->addArgument('start_date', InputArgument::OPTIONAL, 'Set start date in Y-m-d format. Example: 2022-07-29')
            ->addArgument('end_date', InputArgument::OPTIONAL, 'Set end date in Y-m-d format. Example: 2022-07-29')
            ->setHelp(<<<EOT
To use the <info>regulations:pgda:regenerate580</info> command enter a valid date rage values
in Y-m-d format not greater then 7 days either left parameters empty
<info>./console regulations:pgda:regenerate580</info>
<info>./console regulations:pgda:regenerate580 2022-07-01</info>
<info>./console regulations:pgda:regenerate580 2022-07-01 2022-07-08</info>
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null null or 0 if everything went fine, or an error code
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        loadPhive();
        $this->validateProperties($input);
        $this->initProperties($input, $output);

        //fetch open sessions
        $sessions = $this->getExtGameSessionsByRange(
            $this->start_date->toDateTimeString(),
            $this->end_date->toDateTimeString(),
            0
        );

        $count_sessions = count($sessions);
        $progress_bar = new ProgressBar($output, $count_sessions);
        $progress_bar->start();
        $send_start_time = Carbon::now()->toDateTimeString();
        $this->app['monolog']->addInfo("Start time: {$send_start_time}");

        foreach ($sessions as $session) {
            $session = (array)$session;
            $user_id = $this->checkExtGameParticipationUser($session['id']);
            $session['game_code'] = $this->it->getExternalSessionService()->getGameRegulatoryCode($session['ext_game_id']);
            $session['game_type'] = $this->it->getExternalSessionService()->getGameRegulatoryType($session['ext_game_id']);
            $this->app['monolog']->addInfo("session {$session['id']} game_code: {$session['game_code']} game_type: {$session['game_type']}");

            if (empty($session['ended_at']) || $session['ended_at'] == '0000-00-00 00:00:00') {
                $session['ended_at'] = (new Carbon)
                    ->createFromTimestamp(strtotime($session['created_at']) + 900)
                    ->toDateTimeString();
            }
            $ended_at = new Carbon($session['ended_at']);

            //forcing send endParticipation message 430
            $participation_response = lic('endParticipation',
                [$user_id, $session['ended_at'], $session['ext_session_id']], cu($user_id));
            $this->app['monolog']->addInfo("session {$session['id']} message 430 response: $participation_response ");

            if (empty($session['game_code'])) {
                $this->app['monolog']->addWarning("No reference game_code for game {$session['ext_game_id']} code: 21");
                continue;
            }

            $payload = $this->preparePayload($session, $ended_at);

            //forcing send endGameSession message 500
            try {
                $response = lic('endGameSession', [$payload], cu($user_id));
                $this->app['monolog']->addInfo("session {$session['id']} message 500 response: {$response['code']} ");

                if (!in_array($response['code'], [PgdaReturnCode::SUCCESS_CODE, self::ALREADY_CLOSED_SESSION])) {
                    $this->app['monolog']->addInfo("session {$session['id']} not success neither already closed session code:{$response['code']} ");
                    $this->gecs->setGameSessionStatus((int)$session['id'], GECS::STATUS_CODE_NONE,
                        ['code' => $response['code']]);
                    continue;
                }

                $success = $this->gecs->setGameSessionStatus((int)$session['id'], AAMSSessionService::STATUS_CODE);
                $this->sessions_closed++;

                //send message 580
                if ($success) {
                    $this->sendSinglePlayerGameSessionStages((int)$session['id'], $user_id);
                }
                usleep(self::MICRO_SEC_DELAY);

            } catch (Exception $e) {
                $this->app['monolog']->addError(" session: {$session['id']} error {$e->getMessage()}");
            }
            $progress_bar->advance();
        }
        $progress_bar->finish();
        $send_end_time = Carbon::now()->toDateTimeString();
        $this->app['monolog']->addInfo("End time: {$send_end_time}");
        $output->writeln("\nStart time: {$send_start_time}");
        $output->writeln("End time: {$send_end_time}");
        $output->writeln("Period Processed: {$this->start_date} - {$this->end_date}");
        $output->writeln("Sessions processed: {$count_sessions}, sessions closed: {$this->sessions_closed}");
        $output->writeln("Messages sent successfully: {$this->messages_successful}");
        if (file_exists($this->log_file_name)) {
            $output->writeln("Logs stored in file: {$this->log_file_name}");
        }
        $output->writeln("Done");

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function initProperties(InputInterface $input, OutputInterface $output): void
    {
        $this->app = $this->getSilexApplication();
        $this->it = phive('Licensed/IT/IT');
        $this->gecs = new GECS($this->it);
        $this->setOutputLogFileName();

        if (empty($input->getArgument('start_date'))) {
            $this->start_date = Carbon::today()->subDays(self::NUMBER_OF_DAYS_WINDOW);
        } else {
            $this->start_date = Carbon::parse($input->getArgument('start_date'))->startOfDay();
        }


        if (empty($input->getArgument('end_date'))) {
            $this->end_date = Carbon::today()->endOfDay();
        } else {
            // If difference in days between start_date and end_date is over NUMBER_OF_DAYS_WINDOW days limit
            if ($this->start_date->diffInDays(Carbon::parse($input->getArgument('end_date'))) > self::NUMBER_OF_DAYS_WINDOW) {
                $output->writeln(
                    "Difference in days between {$this->start_date->toDateString()}
                    and " . Carbon::now()->toDateString() . " is greater than a week,
                    end_date will be set at start_date + ". self::NUMBER_OF_DAYS_WINDOW . "d"
                );
                $this->end_date = $this->start_date->copy()->addDays(self::NUMBER_OF_DAYS_WINDOW)->endOfDay();
            } else {
                $this->end_date = Carbon::parse($input->getArgument('end_date'))->endOfDay();
            }
        }

    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function validateProperties(InputInterface $input): void
    {
        $date_range_inputs = [
            'start_date' => (string)$input->getArgument('start_date'),
            'end_date' => (string)$input->getArgument('end_date')
        ];

        foreach ($date_range_inputs as $date_input_name => $date_input_value) {
            if (!$this->isValidDateFormat($date_input_value)) {
                throw new InvalidArgumentException("{$date_input_name} is not a string in a valid format YYYY-MM-DD");
            } elseif (Carbon::parse($date_input_value)->isFuture()) {
                throw new InvalidArgumentException("{$date_input_name} is a date in future!");
            }
        }
    }

    /**
     * @param string $date
     * @param string $format
     * @return bool
     */
    private function isValidDateFormat(string $date, string $format = 'Y-m-d'): bool
    {
        try {
            $dt = Carbon::createFromFormat($format, $date);
        } catch (Exception $e) {
            return false;
        }

        return $dt && $dt->format($format) === $date;
    }

    /**
     * @param array $session
     * @param Carbon $ended_at
     * @return array
     */
    private function preparePayload(array $session, Carbon $ended_at): array
    {
        return [
            'game_code' => $session['game_code'],
            'game_type' => $session['game_type'],
            'central_system_session_id' => $session['ext_session_id'],
            'session_end_date' => [
                'date' => [
                    'day' => $ended_at->format('d'),
                    'month' => $ended_at->format('m'),
                    'year' => $ended_at->format('Y'),
                ],
                'time' => [
                    'hour' => $ended_at->format('H'),
                    'minutes' => $ended_at->format('i'),
                    'seconds' => $ended_at->format('s'),
                ],
            ]
        ];
    }

    /**
     * Return user to whom ext_game_session passed is belonging
     * @param int $ext_game_session
     * @return int
     * @throws Exception
     */
    private function checkExtGameParticipationUser(int $ext_game_session_id): int
    {
        return ReplicaDB::table('ext_game_participations')
            ->where('external_game_session_id', '=', $ext_game_session_id)
            ->value('user_id');
    }

    /**
     * Return Array of ext_game_sessions
     * @param string $start_date
     * @param string $end_date
     * @param int $status
     * @return iterable
     * @throws Exception
     */
    private function getExtGameSessionsByRange(string $start_date, string $end_date, int $status = 0): iterable
    {
        return ReplicaDB::table('ext_game_sessions')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->where('status_code', $status)
            ->get()
            ->toArray();
    }

    /**
     * @param int $ext_game_session_id
     * @param int $user_id
     * @return void
     * @throws Exception
     */
    private function sendSinglePlayerGameSessionStages(int $ext_game_session_id, int $user_id): void
    {
        $this->app['monolog']->addInfo("session {$ext_game_session_id} message 580");

        $ext_game_session = $this->gecs->getExtGameSessionById($ext_game_session_id);
        $stages_count = $this->gecs->getSinglePlayerGameSessionStagesCount($ext_game_session, $user_id);
        if ($stages_count > 0) {
            $this->app['monolog']->addNotice("session: {$ext_game_session['id']} ext_session_id: {$ext_game_session['ext_session_id']} stages_count: {$stages_count} user_id: {$user_id}");
        }

        $chunks = (int)ceil($stages_count / $this->gecs::LIMIT);

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $offset = $chunk * $this->gecs::LIMIT;
            $ext_game_session['game_code'] = $this->gecs->getGameCode($ext_game_session['ext_game_id']);
            $ext_game_session['game_type'] = $this->gecs->getGameType($ext_game_session['ext_game_id']);
            $ext_game_session['stages'] = $this->gecs->getSinglePlayerGameSessionStages(
                $ext_game_session_id,
                $user_id,
                $offset
            );
            $ext_game_session['stages_start'] = $offset + 1;
            $ext_game_session['stages_end'] = $offset + count($ext_game_session['stages']);
            $ext_game_session['close'] = ($chunk < $chunks - 1) ? 0 : 1;

            try {
                $payload = $this->gecs->generateSinglePlayerPayload($ext_game_session);
                if (empty($payload['game_stages'])) {
                    throw new LogicException('There is no 580 query');
                }
                // send message
                $response = lic('gameExecutionCommunication', [$payload], cu($user_id));
                $this->outputLog(['580' => ['payload' => [$payload], 'response' => $response]]);
                // response
                if (in_array($response['code'], [PgdaReturnCode::SUCCESS_CODE, self::ALREADY_CLOSED_SESSION])) {
                    $this->gecs->setGameSessionStatus($ext_game_session_id, GECS::STATUS_CODE_SENT);
                    $this->messages_successful++;
                } else {
                    $this->gecs->setGameSessionStatus($ext_game_session_id, GECS::STATUS_CODE_NONE,
                        ['code' => $response['code']]);
                }

            } catch (Exception $e) {
                $this->app['monolog']->addError("ext_game_session_id: {$ext_game_session_id} error {$e->getMessage()}");
            }
        }
    }

    /**
     * @param string|array $log
     * @return void
     */
    private function outputLog($log): void
    {
        file_put_contents(getenv('STORAGE_PATH') . "/{$this->log_file_name}",
            json_encode($log),
            FILE_APPEND | LOCK_EX);
    }

    private function setOutputLogFileName(): void
    {
        $this->log_file_name = sprintf($this->log_file_name, Carbon::now()->format(self::FILE_FORMAT_DATE_TIME));
    }
}