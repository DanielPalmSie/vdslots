<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AlterLocalizedStringForFailedLogins extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $old_value;

    protected array $data = [
        'language' => 'en',
        'alias' => 'blocked.login_fail.html',
        'value' => '<p>Login failed, you have {{attempts}} tries before your account gets locked, if you forgot your password and/or username you can retrieve it <a href="/forgot-password/">here</a>.</p>',
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->old_value = '<p>Login failed, you have 3 tries before your account gets locked, if you forgot your password and/or username you can retrieve it <a href="/forgot-password/">here</a>.</p>';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias',$this->data['alias'])
            ->where('language',$this->data['language'])
            ->update(['value' => $this->data['value']]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias',$this->data['alias'])
            ->where('language',$this->data['language'])
            ->update(['value' => $this->old_value]);
    }
}
