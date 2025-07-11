<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddUsersToPermissionGroup extends Migration
{

    private Connection $connection;

    private array $userIdList;

    private string $tableGroupsMembers;
    private string $brand;

    public function init()
    {

        $this->tableGroupsMembers = 'groups_members';

        $this->connection = DB::getMasterConnection();

        $this->brand = phive('BrandedConfig')->getBrand();

        $this->userIdList = [];


        if($this->brand === 'videoslots') {
            $this->userIdList = [
                '1966971599'
            ];
        }

        if ($this->brand === 'mrvegas') {
            $this->userIdList = [
                '608208'
            ];
        }
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $group_id = $this->getGroupID();
        if($group_id) {
            foreach ($this->userIdList as $userId) {
                $this->connection->table($this->tableGroupsMembers)
                    ->insert([
                        'group_id' => $group_id,
                        'user_id' => $userId
                    ]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $group_id = $this->getGroupID();
        if($group_id) {
            foreach ($this->userIdList as $userId) {
                $this->connection->table($this->tableGroupsMembers)
                    ->where('group_id', '=',  $group_id)
                    ->where('user_id','=',  $userId)
                    ->delete();
            }
        }
    }

    private function getGroupID(): int
    {
        $group = $this->connection
            ->table('groups')
            ->where('name', '=', 'Product Team - view')
            ->first();

        return (int)$group->group_id;
    }
}
