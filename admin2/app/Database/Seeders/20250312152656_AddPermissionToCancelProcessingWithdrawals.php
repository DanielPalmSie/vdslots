<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddPermissionToCancelProcessingWithdrawals extends Seeder
{
    private Connection $connection;

    protected string $permissionGroupsTable = 'permission_groups';
    private string $permissionTagsTable = 'permission_tags';
    private array $groups;
    private array $permissionTag;

    public function init()
    {
        $this->connection = DB::getMasterConnection();

        $this->groups = [
            'Payment and Risk Department',
            'Payment and Risk Department - Head',
            'Payment and Risk Department - Team Leader'
        ];

        $this->permissionTag = [
            'tag' => 'accounting.section.pending-withdrawals.actions.cancel-processing',
            'mod_desc' => '(automatically added)'
        ];
    }

    public function up()
    {
        $this->addPermissionTag();

        foreach ($this->groups as $group_name) {
            $groupId = $this->getGroupID($group_name);

            $result = $this->connection->table($this->permissionGroupsTable)
                ->where('group_id', '=', $groupId)
                ->where('tag', '=', $this->permissionTag['tag'])
                ->exists();

            if (!$result) {
                $this->connection->table($this->permissionGroupsTable)
                    ->where('group_id', $groupId)
                    ->insert([
                        'group_id' => $groupId,
                        'tag' => $this->permissionTag['tag'],
                        'permission' => 'grant'
                    ]);
            }
        }
    }

    public function down()
    {
        $this->removePermissionTag();

        foreach ($this->groups as $group_name) {
            $groupId = $this->getGroupID($group_name);

            $result = $this->connection->table($this->permissionGroupsTable)
                ->where('group_id', '=', $groupId)
                ->where('tag', '=', $this->permissionTag['tag'])
                ->exists();

            if ($result) {
                $this->connection->table($this->permissionGroupsTable)
                    ->where('group_id', '=', $groupId)
                    ->where('tag', '=', $this->permissionTag['tag'])
                    ->delete();
            }
        }
    }


    private function addPermissionTag()
    {
        $tag = $this->connection
            ->table($this->permissionTagsTable)
            ->where('tag', $this->permissionTag['tag'])
            ->first();

        if (empty($tag)) {
            $this->connection
                ->table($this->permissionTagsTable)
                ->insert($this->permissionTag);
        }
    }

    private function removePermissionTag()
    {
        $this->connection
            ->table($this->permissionTagsTable)
            ->where('tag', $this->permissionTag['tag'])
            ->delete();
    }

    private function getGroupID($groupName): int
    {
        $group = $this->connection
            ->table('groups')
            ->where('name', '=', $groupName)
            ->first();

        return (int)$group->group_id;
    }
}
