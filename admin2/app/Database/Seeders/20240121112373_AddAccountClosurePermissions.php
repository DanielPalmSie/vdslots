<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddAccountClosurePermissions extends Seeder
{
    private Connection $connection;
    private string $table;
    private array $permissions;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'permission_tags';
        $this->permissions = [
            ['tag' => 'user.account-closure.fraud_or_ml'],
            ['tag' => 'user.account-closure.rg_concerns'],
            ['tag' => 'user.account-closure.general_closure'],
            ['tag' => 'user.account-closure.duplicate_account'],
            ['tag' => 'user.account-closure.permanent_closure'],
            ['tag' => 'user.account-closure.banned_account'],
        ];
    }

    public function up()
    {
        foreach ($this->permissions as $permission) {
            $exists = $this->connection
                ->table($this->table)
                ->where('tag', '=', $permission['tag'])
                ->exists();

            if (!$exists) {
                $this->connection
                    ->table($this->table)
                    ->insert([$permission]);
            }
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('tag', $this->permissions)
            ->delete();
    }
}
