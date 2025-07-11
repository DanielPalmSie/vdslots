<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class RemoveAccountClosurePermission extends Seeder
{

    private Connection $connection;
    private string $permissionTagsTable;
    private string $permissionGroupsTable;
    private string $permission;



    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->permissionTagsTable = 'permission_tags';
        $this->permissionGroupsTable = 'permission_groups';
        $this->permission = 'user.account-closure.permanent_closure';
    }

    public function up()
    {
        $this->connection
            ->table($this->permissionTagsTable)
            ->where('tag', $this->permission)
            ->delete();

        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('tag', $this->permission)
            ->delete();
    }
}
