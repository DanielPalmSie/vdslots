<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class ChangeAdmin1LogoutLink extends Seeder
{
    private Connection $connection;

    private string $table = 'menus';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'log-out')
            ->update([
                'link' => '/?logout',
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'log-out')
            ->update([
                'link' => 'https://www.videoslots.com/admin_log/?logout',
            ]);
    }
}
