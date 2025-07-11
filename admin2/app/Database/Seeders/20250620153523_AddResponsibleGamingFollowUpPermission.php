<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddResponsibleGamingFollowUpPermission extends Seeder
{
    private Connection $connection;
    private string $groupsTable;
    private string $permissionGroupsTable;
    private string $rgGroup;
    private string $missingPermissionTag;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->rgGroup = 'Responsible Gaming Department - Head';
        $this->missingPermissionTag = 'rg.monitoring.daily-action';
    }

    public function up()
    {
        $groupId = $this->connection
            ->table($this->groupsTable)
            ->where('name', $this->rgGroup)
            ->value('group_id');

        $permission = [
            'group_id' => $groupId,
            'tag' => $this->missingPermissionTag,
            'mod_value' => '',
            'permission' => 'grant',
        ];

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert($permission);
    }

    public function down()
    {
        $groupId = $this->connection
            ->table($this->groupsTable)
            ->where('name', $this->rgGroup)
            ->value('group_id');

        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('group_id', $groupId)
            ->where('tag', $this->missingPermissionTag)
            ->delete();
    }
}
