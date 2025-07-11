<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AdjustProductViewPermissionGroup extends Seeder
{
    private Connection $connection;

    private string $groupsTable;

    private string $permissionGroupsTable;

    public function init()
    {
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $group = $this->connection
            ->table($this->groupsTable)
            ->where('name', 'Product Team - view')
            ->first();

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert([
                'group_id' => $group->group_id,
                'tag' => 'menuer.list-documents',
                'mod_value' => '',
                'permission' => 'grant'
            ]);
    }

    public function down()
    {
        $group = $this->connection
            ->table($this->groupsTable)
            ->where('name', 'Product Team - view')
            ->first();

        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('group_id', $group->group_id)
            ->where('tag', 'menuer.list-documents')
            ->delete();
    }
}
