<?php

namespace App\Commands\Checks;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

abstract class AbstractDatabaseIntegrityCommand extends Command
{
    protected const STORAGE_PATH = 'Checks';
    protected Carbon $date;
    protected string $start_time;
    protected string $end_time;

    protected $sql;
    protected ?Connection $connection;
    protected $app;
    protected string $log_info = '';
    /** @var DatabaseCheckInterface[] */
    protected array $database_checks = [];
    /** @var DatabaseCheckInterface[] */
    protected array $requested_database_checks = [];

    public function __construct(array $database_checks)
    {
        foreach ($database_checks as $check) {
            $this->database_checks[$check->name()] = $check;
        }
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->addArgument(
            'date',
            InputArgument::REQUIRED,
            'Date to be checked (Y-m-d)'
        )
        ->addArgument(
            'checks',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Checks to be done ('.implode(', ', array_keys($this->database_checks)).')'
        );
    }

    abstract public function runCheck(
        DatabaseCheckInterface $database_check,
        InputInterface $input,
        OutputInterface $output
    ): void;

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = 0;
        $this->app = $this->getSilexApplication();
        $this->log_info = 'Ran: ' . json_encode($input->getArguments());
        $this->app['monolog']->addInfo($this->log_info);

        $this->validateInputArguments($input);

        foreach ($this->requested_database_checks as $requested_database_check) {

            try {
                $this->runCheck($requested_database_check, $input, $output);
            } catch (Exception $exception) {
                $this->app['monolog']->addError($this->log_info . "Error: {$exception->getMessage()}");
                $output->writeln(
                    "Failed to run check {$requested_database_check->name()} on {$this->date}: "
                    .$exception->getMessage()
                );
                $result = 1;
            }
        }

        return $result;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->date = Carbon::parse($input->getArgument('date'));
        $this->start_time = $this->date->startOfDay()->format('Y-m-d H:i:s');
        $this->end_time = $this->date->endOfDay()->format('Y-m-d H:i:s');
    }

    abstract protected function getBuilder(
        DatabaseCheckInterface $database_check,
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder;

    protected function applyUsersQueries(Builder $query): void
    {
        $query->whereNotIn('u.id', $this->getTestUsersQuery());
    }

    private function getTestUsersQuery(): Builder
    {
        return $this->connection->table('users_settings')
            ->select(['users_settings.user_id'])
            ->where('users_settings.setting', 'test_account');
    }

    private function validateInputArguments(InputInterface $input): void
    {
        $this->isValidCheckName($input->getArgument('checks'));
        $this->isValidDate($input->getArgument('date'));
    }

    private function isValidCheckName(?array $requested_checks): void
    {
        // if no checks are provided, we check them all
        if (empty($requested_checks)) {
            $requested_checks = array_keys($this->database_checks);
        }

        foreach ($requested_checks as $requested_check) {
            $this->requested_database_checks[] = $this->fetchCheckService($requested_check);
        }
    }

    private function isValidDate(string $date): void
    {
        if (!Carbon::hasFormat($date, 'Y-m-d')) {
            throw new InvalidArgumentException('Wrong date format (it should be like 2024-02-14)');
        }
    }

    /** @throws InvalidArgumentException */
    protected function fetchCheckService(string $check_name): DatabaseCheckInterface
    {
        foreach ($this->database_checks as $database_check) {
            if ($database_check->canRun($check_name)) {
                return $database_check;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid check "%s" (Valid checks: %s)',
            $check_name,
            implode(', ', array_keys($this->database_checks))
        ));
    }
}
