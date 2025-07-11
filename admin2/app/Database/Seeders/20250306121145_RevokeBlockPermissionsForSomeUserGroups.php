<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class RevokeBlockPermissionsForSomeUserGroups extends Seeder
{

    private Connection $connection;
    private string $groupsTable;
    private string $permissionGroupsTable;
    private array $permissionsToRevoke;
    private array $permissionGroups;

    public function init()
    {
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->permissionsToRevoke = ['user.block', 'user.super.block'];
        $this->permissionGroups = [
            'Casino Department - Games',
            'Casino Department - Head',
            'Casino Department - Team Leader',
            'Chief Finance Officer (CFO)',
            'Chief Marketing Officer (CMO)',
            'Finance Department',
            'Finance Department - Head',
            'Marketing Department - CRM',
            'Marketing Department - Head',
            'PSP Manager'
        ];
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->permissionGroups)
            ->pluck('group_id');

        $this->connection
            ->table($this->permissionGroupsTable)
            ->whereIn('group_id', $groupIds)
            ->whereIn('tag', $this->permissionsToRevoke)
            ->delete();
    }

    public function down()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->permissionGroups)
            ->pluck('group_id');

        $permissionGroups = $groupIds->map(function($id) {
            return collect($this->permissionsToRevoke)->map(function($permission) use ($id) {
                return [
                    'group_id' => $id,
                    'tag' => $permission,
                    'mod_value' => '',
                    'permission' => 'grant',
                ];
            });
        })->flatten(1);

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert($permissionGroups->toArray());
    }
}
