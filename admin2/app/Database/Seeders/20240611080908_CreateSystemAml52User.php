<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class CreateSystemAml52User extends Seeder
{
    private const SYSTEM_AML52_PAYOUT_USER = 'system_aml52_payout';

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

        $user = [
            'email' => $systemUser->email ?? '',
            'mobile' => $systemUser->mobile ?? '',
            'country' => $systemUser->country ?? '',
            'sex' => $systemUser->sex ?? '',
            'lastname' => $systemUser->lastname ?? '',
            'firstname' => $systemUser->firstname ?? '',
            'address' => $systemUser->address ?? '',
            'city' => $systemUser->city ?? '',
            'zipcode' => $systemUser->zipcode ?? '',
            'dob' => $systemUser->dob ?? '0000-00-00',
            'preferred_lang' => $systemUser->preferred_lang ?? '',
            'username' => self::SYSTEM_AML52_PAYOUT_USER,
            'password' => $systemUser->password ?? '',
            'bonus_code' => $systemUser->bonus_code ?? '',
            'register_date' => (new DateTimeImmutable())->format('Y-m-d h:i:s'),
            'reg_ip' => $systemUser->reg_ip ?? '',
            'friend' => $systemUser->friend ?? '',
            'alias' => $systemUser->alias ?? '',
            'cur_ip' => $systemUser->cur_ip ?? '',
            'nid' => $systemUser->nid ?? '',
        ];

        if ($this->isShardedDB) {
            phive('SQL')->onlyMaster()->insertArray($this->table, $user);

            $systemAml52User = $this->connection
                ->table($this->table)
                ->where('username', self::SYSTEM_AML52_PAYOUT_USER)
                ->first();

            $user['id'] = $systemAml52User->id;

            phive('SQL')->sh($systemAml52User->id)->insertArray('users', $user);
        } else {
            $this->connection->table($this->table)->insert([$user]);
        }
    }
}
