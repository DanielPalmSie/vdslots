<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ChangeRGNoActionPermissionName extends Seeder
{

    private Connection $connection;
    private string $permissionGroupsTable;
    private string $rgPermission;

    public function init()
    {
        $this->permissionGroupsTable = 'permission_groups';
        $this->rgPermission = 'rg.monitoring.review';
        $this->connection = DB::getMasterConnection();

    }

    public function up()
    {
        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('tag','rg.monitoring.no-action')
            ->update(['tag' => $this->rgPermission]);
    }


    public function down()
    {
        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('tag',$this->rgPermission)
            ->update(['tag' =>'rg.monitoring.no-action']);
    }
}
