<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddPermissionTagsForForcePasswordChangeOnLogin extends Seeder
{
    private Connection $connection;
    private string $groupsTable;
    private string $permissionGroupsTable;
    private string $forcePasswordOnLoginPermission;

    public function init()
    {
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->forcePasswordOnLoginPermission = 'user.force-password-change-on-login';

        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->pluck('group_id');

        $permissionGroups = $groupIds->map(function($id) {
            return [
                'group_id' => $id,
                'tag' => $this->forcePasswordOnLoginPermission,
                'mod_value' => '',
                'permission' => 'grant',
            ];
        });

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert($permissionGroups->toArray());
    }

    public function down()
    {
        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('tag', $this->forcePasswordOnLoginPermission)
            ->delete();
    }
}
