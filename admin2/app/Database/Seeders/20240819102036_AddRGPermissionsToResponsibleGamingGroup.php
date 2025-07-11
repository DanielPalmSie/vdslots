<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddRGPermissionsToResponsibleGamingGroup extends Seeder
{

    private Connection $connection;
    private string $groupsTable;
    private string $permissionGroupsTable;
    private array $rgPermissions;
    private array $permissionGroup;

    public function init()
    {
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->rgPermissions = [
            'rg.monitoring.follow-up-action',
            'rg.monitoring.escalation-action',
            'rg.monitoring.daily-action'
        ];

        $this->connection = DB::getMasterConnection();

        $this->permissionGroup = [
            'Responsible Gaming Department',
            'Responsible Gaming Department - Head',
            'Responsible Gaming Department - Team Leader'
        ];
    }

    public function up()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->permissionGroup)
            ->pluck('group_id');


        $permissionGroups = $groupIds->map(function($id) {
            return collect($this->rgPermissions)->map(function($permission) use ($id) {
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
    public function down()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->permissionGroup)
            ->pluck('group_id');

        $this->connection
            ->table($this->permissionGroupsTable)
            ->whereIn('group_id', $groupIds)
            ->whereIn('tag', $this->rgPermissions)
            ->delete();
    }
}
