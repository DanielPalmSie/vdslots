<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateProductViewPermissionGroup extends Seeder
{

    protected string $tablePermissionGroups;
    private array $permissions;
    private Connection $connection;

    public function init()
    {

        $this->tablePermissionGroups = 'permission_groups';

        $this->connection = DB::getMasterConnection();

        $this->permissions = [
            'permission.view.{group_id}',
            'permission.edit.{group_id}',
        ];

    }

    public function up()
    {
        $group_id = $this->getGroupID();
        foreach ($this->permissions as $permission) {
            $permission = str_replace("{group_id}",$group_id,$permission);

            $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=',  $group_id)
                ->where('tag','=',  $permission)
                ->delete();
        }

        // delete all exiting users from `Product-team-view` group
        $this->connection->table('groups_members')
            ->where('group_id', '=',  $group_id)
            ->delete();
    }

    public function down()
    {
        $group_id = $this->getGroupID();

        foreach ($this->permissions as $permission) {

            $permission = str_replace("{group_id}",$group_id, $permission);

            $result = $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=',  $group_id)
                ->where('tag','=',  $permission)
                ->exists();

            if(!$result) {
                $this->connection->table($this->tablePermissionGroups)
                    ->where('group_id', $group_id)
                    ->insert([
                        'group_id' => $group_id,
                        'tag' => $permission,
                        'permission' => 'grant'
                    ]);
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
