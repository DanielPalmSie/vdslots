<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Models\User;

class CreateSystemUser extends Seeder
{
    private string $table = 'users';
    private Connection $connection;
    private bool $isShardedDB;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    public function up()
    {
        $systemUser = $this->connection
            ->table($this->table)
            ->where('username', 'system')
            ->first();

        if ($systemUser) {
            return;
        }

        $systemUserData = [
            'email' => '',
            'mobile' => '',
            'country' => 'MT',
            'sex' => 'Male',
            'lastname' => 'system',
            'firstname' => 'system',
            'address' => '',
            'city' => '',
            'zipcode' => '',
            'dob' => '0000-00-00',
            'preferred_lang' => 'en',
            'username' => 'system',
            'password' => '',
            'bonus_code' => '',
            'register_date' => (new DateTimeImmutable())->format('Y-m-d h:i:s'),
            'reg_ip' => '',
            'friend' => '',
            'alias' => '',
            'cur_ip' => '',
            'nid' => '',
        ];

        if ($this->isShardedDB) {
            phive('SQL')->onlyMaster()->insertArray($this->table, $systemUserData);

            $systemUser = $this->connection
                ->table($this->table)
                ->where('username', 'system')
                ->first();

            $systemUserData['id'] = $systemUser->id;

            phive('SQL')->sh($systemUser->id)->insertArray('users', $systemUserData);
        } else {
            $this->connection->table($this->table)->insert([$systemUserData]);
        }
    }

    public function down()
    {
    }
}
