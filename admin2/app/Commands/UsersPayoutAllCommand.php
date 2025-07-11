<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\Config;
use App\Repositories\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UsersPayoutAllCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('users:payout:all')
            ->setDescription('Payout all money for users.')
            ->addArgument('country', InputArgument::REQUIRED, 'Apply to users from given country only')
            ->addOption('min_amount', 'm', InputOption::VALUE_REQUIRED, 'Apply to users with minimum balance (in cents)')
            ->addOption('supplier', 's', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter to specific payment_methods')
            ->addOption('previously_self_excluded', '', InputOption::VALUE_NONE,'Include also previously self-excluded users')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Run only for specific users (by id)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force Flag to re-attempt payout for previously failed attempts')
            ->addOption('aml52', null, InputOption::VALUE_NONE, 'Apply only for aml52 self-excluded users')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $country = $input->getArgument('country');
        $aml52Payout = $input->getOption('aml52');

        if ($input->getOption('min_amount')) {
            $min = (int)$input->getOption('min_amount');
        } else if ($aml52Payout){
            $config = Config::where('config_name', 'AML52')->get()->first();
            $values = array_map(fn($value) => explode(':', $value), explode(' ', $config->config_value));
            $limits = array_combine(array_column($values, 0), array_map(fn($limit) => intval(100*$limit), array_column($values, 1)));
            $min = (int)$style->ask(
                'Minimum balance (in cents) to trigger payout?',
                    strval($limits[$country]) ?? null
            );
        } else {
            $min = 0;
        }

        $userHandler = phive('UserHandler');
        $booster = phive('DBUserHandler/Booster');

        if (!empty($input->getOption('supplier'))) {
            $supportedSuppliers = $input->getOption('supplier');
        } else {
            $supportedSuppliers = $userHandler->suppliersAvailableForAutoPayout();
        }

        if ($aml52Payout) {
            $users = UserRepository::usersWithAML52Block(
                $country,
                $min,
                (bool)$input->getOption('previously_self_excluded'),
                $input->getOption('user')
            );
        } else {
            $users = UserRepository::usersFromCountry($country, $min, $input->getOption('user'));
        }

        $force = (bool)$input->getOption('force');

        if (empty($users)) {
            $style->warning('No users found to apply this command!');
            return Command::FAILURE;
        }

        $processed = 0;
        foreach ($users as $user) {
            $style->info(sprintf('User: %d, balance: %0.2f%s.', $user['id'], floatval($user['balance']) / 100, $user['currency']));

            if (!$style->confirm('Proceed?')) {
                continue;
            }

            $vault = (int)$booster->getVaultBalance($user['id']);
            if ($vault > 0 && $booster->releaseBoosterVault($user['id']) === false) {
                $style->warning(sprintf('Error on releasing booster on user %d, skipped!', $user['id']));
                continue;
            }

            $succeed = $aml52Payout ?
                $userHandler->aml52Payout((int)$user['id'], $supportedSuppliers, $force) :
                $userHandler->payoutAll((int)$user['id'], $supportedSuppliers, $force);

            if ($succeed) {
                $style->success(sprintf('Successful refund to user %d.', $user['id']));
                $processed++;
                continue;
            }

            $style->warning(sprintf('Can\'t refund to user: %s. Check logs for more details.', $user['id']));
        }

        $style->info(sprintf('Processed %d of %d all users eligible for payout (based on command options).', $processed, count($users)));

        return Command::SUCCESS;
    }
}
