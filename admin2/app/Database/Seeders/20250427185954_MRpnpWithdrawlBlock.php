<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class MRpnpWithdrawlBlock extends Seeder
{

    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'paynplay.error-popup.proceed' => 'OK',
            'paynplay.withdraw.block.title' => 'Withdrawal Blocked',
            'paynplay.withdraw.block.description' => 'We apologize for the inconvenience, but we regret to inform you that you are currently unable to proceed with your withdrawal. To get the necessary assistance, we kindly request you to reach out to our dedicated customer support team. They are available to help you through Live Chat or via email at support@megariches.com',

            'paynplay.withdraw' => 'Withdraw',
            'paynplay.withdraw.description' => 'Enter the amount you wish to withdraw to your bank account.',
            'paynplay.withdraw.now' => 'Withdraw Now',

            'paynplay.withdraw.success.message' => 'Withdrawal Successful!',
            'paynplay.withdraw.success.description' => 'Your withdrawal is waiting for confirmation, withdrawals are processed daily.',
            'paynplay.withdraw.success.btn'   => 'OK',

            'paynplay.withdraw.failure.message' => 'Withdrawal Failed',
            'paynplay.withdraw.failure.description' => 'The amount is too small.',
            'paynplay.withdraw.failure.low.balance.description' => 'Balance is too low.',
            'paynplay.withdraw.failure.btn'   => 'Ok',

            'paynplay.choose.amount' => 'Choose Amount',
            'paynplay.withdraw.invalidAmountError' => 'Invalid amount. Please enter a number greater than 0.',
            'paynplay.withdraw.invalidAmountPositiveError' => 'Invalid amount. Please enter a positive number.',
            'paynplay.withdraw.invalidNumericalValueError' => 'Invalid amount. Please enter a standard numerical value.',

            'paynplay.deposit' => 'Deposit',
            'paynplay.deposit.btn' => 'Deposit',
            'paynplay.login.without.deposit' => 'Login without Deposit',

            'paynplay.deposit.success.message' => 'Deposit Completed!',
            'paynplay.deposit.success.description' => 'Go to your account start page to see the transaction or to your bonus page to activate applicable first time deposit bonuses.',
            'paynplay.deposit.success.btn'   => 'OK',

            'paynplay.deposit.failure.message' => 'Deposit Failed!',
            'paynplay.deposit.failure.description' => 'Go to your account start page to see the transaction or to your bonus page to activate applicable first time deposit bonuses.',
            'paynplay.deposit.failure.btn'   => 'OK',
            'err.lowbalance'   => 'Balance too low.'
        ],
        'sv' => [
            'err.lowbalance'   => 'Balans för låg',
            'register'         => 'Borja Spela',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->upsert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ], ['alias', 'language']);
            }
        }
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }
    }
}
