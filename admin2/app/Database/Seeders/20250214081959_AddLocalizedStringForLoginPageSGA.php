<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForLoginPageSGA extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'login.with.verification.method')
            ->update(['value' => 'Login with BankID']);

        $this->connection
            ->table($this->table)
            ->insert([
                [
                    'alias' => 'login.with.email.password',
                    'language' => 'en',
                    'value' => 'Login with email and password'
                ],
                [
                    'alias' => 'login.alternative.text',
                    'language' => 'en',
                    'value' => 'You can also choose to login with your email and password'
                ]
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'login.with.verification.method')
            ->update(['value' => 'Log in with BankID']);

        $this->connection
            ->table($this->table)
            ->whereIn('alias', [
                'login.with.email.password',
                'login.alternative.text'
            ])
            ->delete();
    }
}