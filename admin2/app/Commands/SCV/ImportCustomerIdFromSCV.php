<?php

namespace App\Commands\SCV;

use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ParseCsv;
use App\Exceptions\ImportCustomerIdException as ImportCustomerIdException;

class ImportCustomerIdFromSCV extends Command
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName("scv:import-customer-ids")
            ->setDescription("Takes a csv file with user_id and customer_id columns to set the brand link for scv")
            ->addArgument(
                "csv_path",
                InputArgument::REQUIRED,
                "Full path to the csv file"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $output->writeln("Starting import");

        $start_time = Carbon::now();

        $csv_path = $input->getArgument("csv_path");
        if (!file_exists($csv_path)) {
            $output->writeln("File doesn't exist: {$csv_path}");
            return CommandAlias::FAILURE;
        }

        $csv = new ParseCsv\Csv($csv_path);
        $profiles_data = $csv->data;
        $count = count($profiles_data);

        if ($count < 1) {
            $output->writeln("File is empty: {$csv_path}");
            return CommandAlias::FAILURE;
        }

        if (
            !array_key_exists('user_id', $profiles_data[0]) ||
            !array_key_exists('customer_id', $profiles_data[0])
        ) {
            $output->writeln("File is not in the correct format. Columns user_id and customer_id are required.");
            return CommandAlias::FAILURE;
        }

        $setting = distKey('scv');
        $system_user_id = cu('system')->getId();

        foreach ($profiles_data as $key => $profile) {
            $output->write("{$key}/{$count}|");
            $user_id = $profile['user_id'];
            $customer_id = $profile['customer_id'];
            $db = phive('SQL')->sh($user_id);

            if (empty($customer_id) || empty($user_id)) {
                $output->writeln("customer_id_or_user_id_error: {$user_id} {$customer_id}|");
                continue;
            }

            try {
                $db->beginTransaction();

                $success = $db->query("
                    INSERT INTO users_settings (user_id, setting, value)
                    VALUES ('{$user_id}', '{$setting}', '{$customer_id}');
                ");
                if (!$success) {
                    throw new ImportCustomerIdException("us_error: {$user_id}|");
                }

                $success = $db->query("
                    INSERT INTO actions (actor, target, descr, tag, actor_username)
                    VALUES (
                        {$system_user_id},
                        {$user_id},
                        '{$setting} has been set to: {$customer_id}',
                        '$setting',
                        'system'
                      );
                ");

                if (!$success) {
                    throw new ImportCustomerIdException("action_error: {$user_id}|");
                }

                $db->commitTransaction();
            } catch (ImportCustomerIdException $e) {
                $db->rollbackTransaction();
                $output->writeln($e->getMessage());
            }
        }

        $end_time = Carbon::now();
        $output->writeln("{$start_time} - {$end_time}}");
        $output->writeln("Finished importing customer ids");

        return CommandAlias::SUCCESS;
    }
}
