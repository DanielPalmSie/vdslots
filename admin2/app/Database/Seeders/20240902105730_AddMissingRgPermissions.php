<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddMissingRgPermissions extends Seeder
{
    private Connection $connection;
    private string $groupsTable;
    private string $permissionGroupsTable;
    private array $rgGroups;
    private array $missingPermissionTags;

    public function init()
    {
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';

        $this->rgGroups = [
            'Chief Operations Officer (COO)',
            'Responsible Gaming Department',
            'Responsible Gaming Department - Team Leader',
            'Responsible Gaming Department - Head'
        ];

        $this->missingPermissionTags = [
            'rg.monitoring.force-self-exclusion',
            'rg.monitoring.vulnerability-check',
            'rg.monitoring.affordability-check'
        ];

        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->rgGroups)
            ->pluck('group_id');

        $permissions = [];
        foreach ($groupIds as $groupId) {
            foreach ($this->missingPermissionTags as $tag) {
                $permissions[] = [
                    'group_id' => $groupId,
                    'tag' => $tag,
                    'mod_value' => '',
                    'permission' => 'grant',
                ];
            }
        }

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert($permissions);
    }

    public function down()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->rgGroups)
            ->pluck('group_id');

        $this->connection
            ->table($this->permissionGroupsTable)
            ->whereIn('group_id', $groupIds)
            ->whereIn('tag', $this->missingPermissionTags)
            ->delete();
    }
}
