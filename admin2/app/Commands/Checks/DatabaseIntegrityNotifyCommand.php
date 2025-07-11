<?php

namespace App\Commands\Checks;

use App\Extensions\Database\ReplicaFManager as DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseIntegrityNotifyCommand extends AbstractDatabaseIntegrityCommand
{
    protected bool $isDataIntegrityOk = true;

    protected function configure(): void
    {
        parent::configure();

        $this->setName('data-integrity:check')
            ->setDescription('Checks database for broken/faulty data and notifies them to Slack if it is requested.')
            ->addOption(
                'notify',
                null,
                InputOption::VALUE_NONE,
                'Send a notification to Slack'
            );
    }

    public function runCheck(
        DatabaseCheckInterface $database_check,
        InputInterface $input,
        OutputInterface $output
    ): void {

        $output->write("[{$database_check->name()}] ");

        $this->checkDataIntegrity($database_check);

        if ($this->isDataIntegrityOk) {
            $output->writeln("<info>Ok</info>");
            return;
        }

        $output->writeln("<error>KO</error>");

        if ($input->getOption('notify')) {
            $this->notify($database_check, $output);
        }
    }

    private function checkDataIntegrity(DatabaseCheckInterface $database_check): void
    {
        $do_master = !count(DB::getNodesList());

        if ($do_master) {
            $this->processNode(DB::getMasterConnection(), $database_check);
        }

        DB::loopNodes(
            function (Connection $connection) use ($database_check) {
                $this->processNode($connection, $database_check);
            });
    }

    private function processNode(Connection $connection, DatabaseCheckInterface $database_check): void
    {
        if (!$this->isDataIntegrityOk) {
            return;
        }

        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        $builder = $this->getBuilder($database_check, $this->connection, $this->start_time, $this->end_time);

        if ($database_check->requiresUserData()) {
            $this->applyUsersQueries($builder);
        }

        $this->isDataIntegrityOk = $this->isDataIntegrityOk && !$builder->exists();
    }

    protected function getBuilder(
        DatabaseCheckInterface $database_check,
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder{

        return $database_check->getBuilderForAny($connection, $start_time, $end_time);
    }

    protected function notify(
        DatabaseCheckInterface $database_check,
        OutputInterface $output
    ): void {

        $env = $this->app['env'];
        $message = sprintf(
            ($env !== 'prod' ? '['.$env.'] ' : '' ).
            'Data Integrity Alert! Check "%s" on "%s"',
            $database_check->name(),
            $this->date->format('Y-m-d')
        );

        $this->app['slack.logger.reporting']->warning($message);

        $output->writeln("Notification sent");
    }
}
